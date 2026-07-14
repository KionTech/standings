<script setup lang="ts">
import { update as setMainCharacterAction } from '@/actions/App/Http/Controllers/MainCharacterController';
import { store as completeSetupAction } from '@/actions/App/Http/Controllers/SetupController';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useInitials } from '@/composables/useInitials';
import { eveImage } from '@/lib/eve';
import { login } from '@/routes';
import { router } from '@inertiajs/vue3';
import { useLocalStorage } from '@vueuse/core';
import {
    Check,
    MessageSquarePlus,
    Plus,
    RefreshCw,
    Star,
    Users,
} from '@lucide/vue';
import { computed, ref } from 'vue';

type WizardCharacter = {
    id: number;
    name: string;
};

const props = defineProps<{
    characters: WizardCharacter[];
}>();

const { getInitials } = useInitials();

const TOTAL_STEPS = 4;

const STORAGE_KEY = 'standings.setup-wizard-step';

const open = ref(true);

// Remember the current step across page loads, so the wizard resumes where
// the user left off after adding a character through the EVE SSO redirect.
const step = useLocalStorage(STORAGE_KEY, 0);

if (step.value < 0 || step.value >= TOTAL_STEPS) {
    step.value = 0;
}

const selectedMainId = ref<number | null>(
    props.characters.length === 1 ? props.characters[0].id : null,
);

const isLastStep = computed(() => step.value === TOTAL_STEPS - 1);

function next(): void {
    if (!isLastStep.value) {
        step.value += 1;
    }
}

function back(): void {
    if (step.value > 0) {
        step.value -= 1;
    }
}

function close(): void {
    open.value = false;
    step.value = 0;
    localStorage.removeItem(STORAGE_KEY);
}

function skip(): void {
    router.post(
        completeSetupAction.url(),
        {},
        {
            preserveScroll: true,
            onSuccess: close,
        },
    );
}

function finish(): void {
    if (selectedMainId.value === null) {
        skip();
        return;
    }

    router.put(
        setMainCharacterAction.url({ character: selectedMainId.value }),
        {},
        {
            preserveScroll: true,
            onSuccess: close,
        },
    );
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>
                    <template v-if="step === 0">Welcome, pilot</template>
                    <template v-else-if="step === 1"
                        >Add all your characters</template
                    >
                    <template v-else-if="step === 2"
                        >Standings sync themselves</template
                    >
                    <template v-else>Pick your main character</template>
                </DialogTitle>
                <DialogDescription>
                    Step {{ step + 1 }} of {{ TOTAL_STEPS }}
                </DialogDescription>
            </DialogHeader>

            <!-- Step 1: what this is -->
            <div v-if="step === 0" class="space-y-4 text-sm">
                <p>
                    This app keeps your in-game contacts in line with the
                    alliance's official standings — automatically, for every
                    character you register.
                </p>
                <ul class="space-y-3">
                    <li class="flex items-start gap-3">
                        <Users
                            class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground"
                        />
                        <span>
                            You add all your characters to one account.
                        </span>
                    </li>
                    <li class="flex items-start gap-3">
                        <RefreshCw
                            class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground"
                        />
                        <span>
                            The alliance's standings are copied onto each
                            character's contact list and kept up to date.
                        </span>
                    </li>
                    <li class="flex items-start gap-3">
                        <MessageSquarePlus
                            class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground"
                        />
                        <span>
                            If one of your characters isn't blue yet, you can
                            request a standing right from the dashboard.
                        </span>
                    </li>
                </ul>
            </div>

            <!-- Step 2: add characters -->
            <div v-else-if="step === 1" class="space-y-4 text-sm">
                <p>
                    Every character you want to keep in sync needs to be on this
                    account — alts included. Log in with each one once and it
                    joins automatically.
                </p>
                <div>
                    <p class="mb-2 text-xs text-muted-foreground">
                        Currently on your account:
                    </p>
                    <ul class="max-h-40 space-y-1 overflow-y-auto">
                        <li
                            v-for="character in characters"
                            :key="character.id"
                            class="flex items-center gap-3 rounded-md border px-3 py-2"
                        >
                            <Avatar class="h-7 w-7 overflow-hidden rounded">
                                <AvatarImage
                                    :src="eveImage('character', character.id)!"
                                    :alt="character.name"
                                />
                                <AvatarFallback class="rounded text-xs">
                                    {{ getInitials(character.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <span class="text-sm font-medium">
                                {{ character.name }}
                            </span>
                        </li>
                    </ul>
                </div>
                <a :href="login.url() + '?add_to_account=1'">
                    <Button type="button" variant="outline" class="w-full">
                        <Plus class="h-4 w-4" />
                        Add another character
                    </Button>
                </a>
                <p class="text-xs text-muted-foreground">
                    You can always add more later from the account menu in the
                    bottom left. Already added everyone? Continue.
                </p>
            </div>

            <!-- Step 3: how syncing works -->
            <div v-else-if="step === 2" class="space-y-4 text-sm">
                <p>
                    Each character on the dashboard has a sync switch. Turn it
                    on and their in-game contacts mirror the alliance's
                    standings within minutes.
                </p>
                <ul class="space-y-3">
                    <li class="flex items-start gap-3">
                        <Check
                            class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground"
                        />
                        <span>
                            Characters already in the alliance inherit the
                            standings in-game — nothing to do for those.
                        </span>
                    </li>
                    <li class="flex items-start gap-3">
                        <Check
                            class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground"
                        />
                        <span>
                            For everyone else, use
                            <span class="font-medium">Request standing</span>
                            — an admin reviews it, and once approved the
                            standings unlock for you.
                        </span>
                    </li>
                </ul>
            </div>

            <!-- Step 4: pick a main -->
            <div v-else class="space-y-4 text-sm">
                <p>
                    Your main is the character the admins will recognize you by
                    — your alts are grouped under it in the pilot roster.
                </p>
                <ul class="max-h-56 space-y-1 overflow-y-auto">
                    <li v-for="character in characters" :key="character.id">
                        <button
                            type="button"
                            class="flex w-full items-center gap-3 rounded-md border px-3 py-2 text-left transition-colors hover:bg-muted"
                            :class="{
                                'border-primary bg-muted':
                                    selectedMainId === character.id,
                            }"
                            :data-test="`wizard-character-${character.id}`"
                            @click="selectedMainId = character.id"
                        >
                            <Avatar class="h-8 w-8 overflow-hidden rounded">
                                <AvatarImage
                                    :src="eveImage('character', character.id)!"
                                    :alt="character.name"
                                />
                                <AvatarFallback class="rounded text-xs">
                                    {{ getInitials(character.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <span class="flex-1 text-sm font-medium">
                                {{ character.name }}
                            </span>
                            <Check
                                v-if="selectedMainId === character.id"
                                class="h-4 w-4 text-primary"
                            />
                        </button>
                    </li>
                </ul>
                <p class="text-xs text-muted-foreground">
                    You can change this later with the star next to a character
                    on the dashboard.
                </p>
            </div>

            <div class="flex items-center justify-between gap-2 pt-2">
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="text-muted-foreground"
                    @click="skip"
                >
                    Skip for now
                </Button>
                <div class="flex items-center gap-2">
                    <Button
                        v-if="step > 0"
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="back"
                    >
                        Back
                    </Button>
                    <Button
                        v-if="!isLastStep"
                        type="button"
                        size="sm"
                        @click="next"
                    >
                        Next
                    </Button>
                    <Button
                        v-else
                        type="button"
                        size="sm"
                        :disabled="selectedMainId === null"
                        @click="finish"
                    >
                        <Star class="h-4 w-4" />
                        Set main & finish
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
