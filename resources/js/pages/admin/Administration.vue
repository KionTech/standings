<script setup lang="ts">
import { index } from '@/actions/App/Http/Controllers/Admin/AdministrationController';
import { update as updateDiscord } from '@/actions/App/Http/Controllers/Admin/DiscordSettingController';
import { update as updateRequest } from '@/actions/App/Http/Controllers/Admin/StandingRequestController';
import {
    sync as syncSource,
    update as updateSource,
} from '@/actions/App/Http/Controllers/Admin/StandingsSourceController';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useInitials } from '@/composables/useInitials';
import { countdown, dateWithAgo } from '@/lib/date';
import { eveImage } from '@/lib/eve';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm, usePoll } from '@inertiajs/vue3';
import { useNow } from '@vueuse/core';
import { Check, Copy, RefreshCw } from '@lucide/vue';
import { computed, ref } from 'vue';

const REFRESH_INTERVAL_MS = 5 * 60 * 1000;

type SourceTypeOption = {
    value: string;
    label: string;
    entity_id: number | null;
    entity_name: string | null;
    has_scope: boolean;
    available: boolean;
};

type StandingRequestItem = {
    id: number;
    status: string;
    created_at: string;
    subject: { type: string; id: number; name: string | null };
    requested_by: string;
};

const props = defineProps<{
    source: {
        type: string;
        entity_id: number;
        entity_name: string | null;
        last_synced_at: string | null;
    } | null;
    sourceTypes: SourceTypeOption[];
    adminCharacter: { id: number; name: string } | null;
    contactsCount: number;
    discordSettings: { webhook_url: string | null; role_id: string | null };
    standingRequests: StandingRequestItem[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: index() },
];

// Refresh server props every 30s so incoming standing requests appear live.
usePoll(30000);

const { getInitials } = useInitials();

// A clock that ticks every second so the relative times stay live.
const now = useNow({ interval: 1000 });

const lastRefreshed = computed(() =>
    dateWithAgo(props.source?.last_synced_at, now.value.getTime()),
);

const nextRefresh = computed(() => {
    if (!props.source?.last_synced_at) {
        return null;
    }

    const target =
        new Date(props.source.last_synced_at).getTime() + REFRESH_INTERVAL_MS;

    return countdown(target, now.value.getTime());
});

const sourceForm = useForm<{ type: string }>({
    type: props.source?.type ?? '',
});

const discordForm = useForm({
    webhook_url: props.discordSettings.webhook_url ?? '',
    role_id: props.discordSettings.role_id ?? '',
});

const changeSourceOpen = ref(false);

function saveSource(): void {
    sourceForm.put(updateSource.url(), {
        preserveScroll: true,
        onSuccess: () => {
            changeSourceOpen.value = false;
        },
    });
}

function saveDiscord(): void {
    discordForm.put(updateDiscord.url(), { preserveScroll: true });
}

function syncNow(): void {
    router.post(syncSource.url(), {}, { preserveScroll: true });
}

const copiedId = ref<number | null>(null);

function copyName(request: StandingRequestItem): void {
    const name = request.subject.name ?? String(request.subject.id);

    navigator.clipboard?.writeText(name).catch(() => {});

    copiedId.value = request.id;
    window.setTimeout(() => {
        if (copiedId.value === request.id) {
            copiedId.value = null;
        }
    }, 1500);
}

function resolveRequest(
    request: StandingRequestItem,
    status: 'done' | 'rejected',
): void {
    router.put(
        updateRequest.url({ standingRequest: request.id }),
        { status },
        { preserveScroll: true },
    );
}

function subjectImage(type: string, id: number): string {
    return eveImage(type, id) ?? '';
}

function statusVariant(
    status: string,
): 'secondary' | 'outline' | 'destructive' {
    if (status === 'done') {
        return 'secondary';
    }
    if (status === 'rejected') {
        return 'destructive';
    }
    return 'outline';
}
</script>

<template>
    <Head title="Administration" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Source (read-only with confirm-to-change) -->
                <Card>
                    <CardHeader
                        class="flex flex-row items-start justify-between gap-4"
                    >
                        <div class="flex items-center gap-3">
                            <Avatar
                                v-if="source"
                                class="h-10 w-10 shrink-0 overflow-hidden rounded"
                            >
                                <AvatarImage
                                    :src="
                                        subjectImage(
                                            source.type,
                                            source.entity_id,
                                        )
                                    "
                                    :alt="source.entity_name ?? ''"
                                />
                                <AvatarFallback
                                    class="rounded text-xs uppercase"
                                >
                                    {{ source.type.slice(0, 2) }}
                                </AvatarFallback>
                            </Avatar>
                            <div>
                                <CardTitle>Standings source</CardTitle>
                                <CardDescription>
                                    <template v-if="source">
                                        <span
                                            class="font-medium text-foreground"
                                            >{{
                                                source.entity_name ??
                                                source.entity_id
                                            }}</span
                                        >
                                        <span class="capitalize"
                                            >&middot; {{ source.type }}</span
                                        >
                                    </template>
                                    <template v-else>
                                        No source has been configured yet.
                                    </template>
                                </CardDescription>
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <Dialog v-model:open="changeSourceOpen">
                                <DialogTrigger as-child>
                                    <Button
                                        type="button"
                                        size="sm"
                                        :variant="
                                            source ? 'destructive' : 'default'
                                        "
                                    >
                                        {{
                                            source
                                                ? 'Change source'
                                                : 'Set source'
                                        }}
                                    </Button>
                                </DialogTrigger>
                                <DialogContent class="sm:max-w-md">
                                    <DialogHeader>
                                        <DialogTitle>
                                            {{
                                                source
                                                    ? 'Change standings source?'
                                                    : 'Set standings source'
                                            }}
                                        </DialogTitle>
                                        <DialogDescription>
                                            <template v-if="source">
                                                Changing the source clears the
                                                current standings and re-syncs
                                                every character.
                                            </template>
                                            <template v-else>
                                                Select which of your character,
                                                corporation or alliance defines
                                                the canonical standings.
                                            </template>
                                        </DialogDescription>
                                    </DialogHeader>
                                    <form
                                        class="space-y-4"
                                        @submit.prevent="saveSource"
                                    >
                                        <div class="space-y-2">
                                            <Label for="type">Source</Label>
                                            <Select v-model="sourceForm.type">
                                                <SelectTrigger
                                                    id="type"
                                                    class="w-full"
                                                >
                                                    <SelectValue
                                                        placeholder="Select your character, corp or alliance"
                                                    />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem
                                                        v-for="type in sourceTypes"
                                                        :key="type.value"
                                                        :value="type.value"
                                                        :disabled="
                                                            !type.available
                                                        "
                                                    >
                                                        {{ type.label }}
                                                        <span
                                                            v-if="
                                                                type.entity_name
                                                            "
                                                            class="text-muted-foreground"
                                                        >
                                                            &mdash;
                                                            {{
                                                                type.entity_name
                                                            }}
                                                        </span>
                                                        <span
                                                            v-else-if="
                                                                !type.has_scope
                                                            "
                                                            class="text-muted-foreground"
                                                        >
                                                            (no permission)
                                                        </span>
                                                        <span
                                                            v-else
                                                            class="text-muted-foreground"
                                                        >
                                                            (unavailable)
                                                        </span>
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <p
                                                v-if="sourceForm.errors.type"
                                                class="text-sm text-destructive"
                                            >
                                                {{ sourceForm.errors.type }}
                                            </p>
                                        </div>
                                        <DialogFooter>
                                            <DialogClose as-child>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                >
                                                    Cancel
                                                </Button>
                                            </DialogClose>
                                            <Button
                                                type="submit"
                                                :disabled="
                                                    sourceForm.processing
                                                "
                                            >
                                                {{
                                                    source
                                                        ? 'Change source'
                                                        : 'Save source'
                                                }}
                                            </Button>
                                        </DialogFooter>
                                    </form>
                                </DialogContent>
                            </Dialog>
                            <Button
                                v-if="source"
                                type="button"
                                variant="secondary"
                                size="sm"
                                @click="syncNow"
                            >
                                <RefreshCw class="h-4 w-4" />
                                Sync now
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div
                            v-if="!adminCharacter"
                            class="mb-4 rounded-md border border-destructive/40 bg-destructive/10 px-4 py-3 text-sm text-destructive"
                        >
                            Your active character could not be resolved.
                        </div>

                        <dl
                            v-if="source"
                            class="grid gap-3 text-sm sm:grid-cols-3"
                        >
                            <div>
                                <dt class="text-muted-foreground">
                                    Canonical contacts
                                </dt>
                                <dd class="font-medium">{{ contactsCount }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">
                                    Last refreshed
                                </dt>
                                <dd class="font-medium">{{ lastRefreshed }}</dd>
                            </div>
                            <div v-if="nextRefresh">
                                <dt class="text-muted-foreground">
                                    Next refresh
                                </dt>
                                <dd class="font-medium tabular-nums">
                                    {{ nextRefresh }}
                                </dd>
                            </div>
                        </dl>
                        <p v-else class="text-sm text-muted-foreground">
                            No source is configured yet.
                        </p>
                    </CardContent>
                </Card>

                <!-- Discord notifications -->
                <Card>
                    <CardHeader>
                        <CardTitle>Discord notifications</CardTitle>
                        <CardDescription>
                            Where standing requests are announced. Optionally
                            ping a role.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form class="space-y-4" @submit.prevent="saveDiscord">
                            <div class="grid gap-4 sm:grid-cols-[2fr_1fr]">
                                <div class="space-y-2">
                                    <Label for="webhook_url">Webhook URL</Label>
                                    <Input
                                        id="webhook_url"
                                        v-model="discordForm.webhook_url"
                                        type="url"
                                        placeholder="https://discord.com/api/webhooks/..."
                                    />
                                    <p
                                        v-if="discordForm.errors.webhook_url"
                                        class="text-sm text-destructive"
                                    >
                                        {{ discordForm.errors.webhook_url }}
                                    </p>
                                </div>
                                <div class="space-y-2">
                                    <Label for="role_id"
                                        >Role to ping (optional)</Label
                                    >
                                    <Input
                                        id="role_id"
                                        v-model="discordForm.role_id"
                                        placeholder="Role ID"
                                    />
                                    <p
                                        v-if="discordForm.errors.role_id"
                                        class="text-sm text-destructive"
                                    >
                                        {{ discordForm.errors.role_id }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex justify-end">
                                <Button
                                    type="submit"
                                    :disabled="discordForm.processing"
                                >
                                    Save Discord settings
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>

            <!-- Standing requests -->
            <Card>
                <CardHeader>
                    <CardTitle>Standing requests</CardTitle>
                    <CardDescription>
                        Pilots asking for a character, corporation or alliance
                        to be added to the standings.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="standingRequests.length === 0"
                        class="py-8 text-center text-sm text-muted-foreground"
                    >
                        No standing requests yet.
                    </p>
                    <ul v-else class="-mx-2">
                        <li
                            v-for="request in standingRequests"
                            :key="request.id"
                            class="flex items-center gap-4 rounded-md px-2 py-3 transition-colors odd:bg-muted/40 hover:bg-muted"
                        >
                            <Avatar class="h-9 w-9 overflow-hidden rounded">
                                <AvatarImage
                                    :src="
                                        subjectImage(
                                            request.subject.type,
                                            request.subject.id,
                                        )
                                    "
                                    :alt="request.subject.name ?? ''"
                                />
                                <AvatarFallback class="rounded text-xs">
                                    {{
                                        getInitials(request.subject.name ?? '?')
                                    }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="flex-1">
                                <p class="text-sm font-medium">
                                    {{
                                        request.subject.name ??
                                        request.subject.id
                                    }}
                                    <span
                                        class="text-xs text-muted-foreground capitalize"
                                    >
                                        ({{ request.subject.type }})
                                    </span>
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Requested by {{ request.requested_by }}
                                </p>
                            </div>

                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8"
                                :aria-label="`Copy ${request.subject.name}`"
                                @click="copyName(request)"
                            >
                                <Check
                                    v-if="copiedId === request.id"
                                    class="h-4 w-4"
                                />
                                <Copy v-else class="h-4 w-4" />
                            </Button>

                            <template v-if="request.status === 'pending'">
                                <Dialog>
                                    <DialogTrigger as-child>
                                        <Button type="button" size="sm">
                                            Mark done
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent class="sm:max-w-md">
                                        <DialogHeader>
                                            <DialogTitle
                                                >Mark request as
                                                done?</DialogTitle
                                            >
                                            <DialogDescription>
                                                Confirm that
                                                <span
                                                    class="font-medium text-foreground"
                                                    >{{
                                                        request.subject.name
                                                    }}</span
                                                >
                                                has been added to the standings.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <DialogFooter>
                                            <DialogClose as-child>
                                                <Button variant="outline"
                                                    >Cancel</Button
                                                >
                                            </DialogClose>
                                            <Button
                                                @click="
                                                    resolveRequest(
                                                        request,
                                                        'done',
                                                    )
                                                "
                                            >
                                                Confirm
                                            </Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>

                                <Dialog>
                                    <DialogTrigger as-child>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="outline"
                                        >
                                            Reject
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent class="sm:max-w-md">
                                        <DialogHeader>
                                            <DialogTitle
                                                >Reject request?</DialogTitle
                                            >
                                            <DialogDescription>
                                                Reject the standing request for
                                                <span
                                                    class="font-medium text-foreground"
                                                    >{{
                                                        request.subject.name
                                                    }}</span
                                                >?
                                            </DialogDescription>
                                        </DialogHeader>
                                        <DialogFooter>
                                            <DialogClose as-child>
                                                <Button variant="outline"
                                                    >Cancel</Button
                                                >
                                            </DialogClose>
                                            <Button
                                                variant="destructive"
                                                @click="
                                                    resolveRequest(
                                                        request,
                                                        'rejected',
                                                    )
                                                "
                                            >
                                                Confirm
                                            </Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>
                            </template>
                            <Badge
                                v-else
                                :variant="statusVariant(request.status)"
                            >
                                {{ request.status }}
                            </Badge>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
