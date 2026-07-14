<script setup lang="ts">
import { index as overviewRoute } from '@/actions/App/Http/Controllers/Admin/AdministrationController';
import {
    index as requestsRoute,
    update as updateRequest,
} from '@/actions/App/Http/Controllers/Admin/StandingRequestController';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
import Pagination from '@/components/Pagination.vue';
import { useInitials } from '@/composables/useInitials';
import { eveImage, standingLabel, standingTextClass } from '@/lib/eve';
import AdminLayout from '@/layouts/admin/Layout.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Paginated } from '@/types';
import { Head, router, usePoll } from '@inertiajs/vue3';
import { Check, Copy } from '@lucide/vue';
import { computed, ref } from 'vue';

type EffectiveStanding = {
    standing: number;
    source: 'direct' | 'corporation' | 'alliance';
    via_type: string;
    via_id: number;
    via_name: string | null;
};

type StandingRequestItem = {
    id: number;
    status: string;
    created_at: string;
    subject: { type: string; id: number; name: string | null };
    requested_by: string;
    effective_standing: EffectiveStanding | null;
};

const props = defineProps<{
    standingRequests: Paginated<StandingRequestItem>;
    counts: { pending: number; done: number; rejected: number };
    filters: { status: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: overviewRoute() },
    { title: 'Standing requests', href: requestsRoute() },
];

// Refresh server props every 30s so incoming standing requests appear live.
usePoll(30000);

const { getInitials } = useInitials();

const STATUS_FILTERS = ['pending', 'done', 'rejected'] as const;

type StatusFilter = (typeof STATUS_FILTERS)[number];

// Only one status is loaded at a time; picking another asks the server for
// that slice, so resolved requests stay out of the default pending view.
const statusFilter = computed<StatusFilter>(() =>
    STATUS_FILTERS.includes(props.filters.status as StatusFilter)
        ? (props.filters.status as StatusFilter)
        : 'pending',
);

function setStatusFilter(filter: StatusFilter): void {
    router.get(
        requestsRoute.url(),
        filter === 'pending' ? {} : { status: filter },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['standingRequests', 'counts', 'filters'],
        },
    );
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
    <Head title="Standing requests" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <AdminLayout
            title="Standing requests"
            description="Pilots asking for a character, corporation or alliance to be added to the standings."
        >
            <template #actions>
                <div
                    class="flex items-center gap-1 rounded-lg border bg-card p-1"
                    role="group"
                    aria-label="Filter by status"
                >
                    <Button
                        v-for="filter in STATUS_FILTERS"
                        :key="filter"
                        type="button"
                        size="sm"
                        :variant="
                            statusFilter === filter ? 'secondary' : 'ghost'
                        "
                        class="h-7 px-2.5 text-xs capitalize"
                        @click="setStatusFilter(filter)"
                    >
                        {{ filter }}
                        <span class="text-muted-foreground tabular-nums">
                            {{ counts[filter] }}
                        </span>
                    </Button>
                </div>
            </template>

            <Card>
                <CardContent>
                    <p
                        v-if="standingRequests.data.length === 0"
                        class="py-10 text-center text-sm text-muted-foreground"
                    >
                        <template v-if="statusFilter === 'pending'">
                            No pending standing requests. New requests from
                            pilots appear here.
                        </template>
                        <template v-else>
                            No {{ statusFilter }} requests.
                        </template>
                    </p>
                    <ul v-else class="-mx-2">
                        <li
                            v-for="request in standingRequests.data"
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
                                <p class="text-xs">
                                    <template v-if="request.effective_standing">
                                        <span
                                            class="font-medium"
                                            :class="
                                                standingTextClass(
                                                    request.effective_standing
                                                        .standing,
                                                )
                                            "
                                        >
                                            {{
                                                standingLabel(
                                                    request.effective_standing
                                                        .standing,
                                                )
                                            }}
                                        </span>
                                        <span class="text-muted-foreground">
                                            {{
                                                request.effective_standing
                                                    .source === 'direct'
                                                    ? 'set directly'
                                                    : `inherited via ${request.effective_standing.via_name ?? request.effective_standing.via_type}`
                                            }}
                                        </span>
                                    </template>
                                    <span v-else class="text-muted-foreground">
                                        No standing yet
                                    </span>
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
                    <div
                        v-if="standingRequests.last_page > 1"
                        class="mt-4 border-t pt-4"
                    >
                        <Pagination :links="standingRequests.links" />
                    </div>
                </CardContent>
            </Card>
        </AdminLayout>
    </AppLayout>
</template>
