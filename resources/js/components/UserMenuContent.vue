<script setup lang="ts">
import {
    destroy as destroyCharacter,
    update as updateCharacter,
} from '@/actions/App/Http/Controllers/UserCharacterController';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import UserInfo from '@/components/UserInfo.vue';
import { useInitials } from '@/composables/useInitials';
import { login, logout } from '@/routes';
import { edit as editAppearance } from '@/routes/appearance';
import type { Character } from '@/types';
import { Link, router, usePage } from '@inertiajs/vue3';
import { LogOut, Minus, Plus, Settings } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const page = usePage();
const auth = computed(() => page.props.auth);
const { getInitials } = useInitials();
const showRemoveDialog = ref(false);

const otherCharacters = computed(() =>
    auth.value.characters.filter(
        (c: Character) => c.id !== auth.value.active_character.id,
    ),
);

const isLastCharacter = computed(() => auth.value.characters.length <= 1);

function portraitUrl(characterId: number): string {
    return `https://images.evetech.net/characters/${characterId}/portrait?size=64`;
}

function switchCharacter(character: Character) {
    router.put(
        updateCharacter.url({ character: character.id }),
        {},
        { preserveScroll: true },
    );
}

function confirmRemoveCharacter() {
    router.delete(
        destroyCharacter.url({
            character: auth.value.active_character.id,
        }),
        { preserveScroll: true },
    );
}

const handleLogout = () => {
    router.flushAll();
};
</script>

<template>
    <!-- Active character -->
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :character="auth.active_character" />
        </div>
    </DropdownMenuLabel>

    <!-- Other characters -->
    <template v-if="otherCharacters.length > 0">
        <DropdownMenuSeparator />
        <DropdownMenuLabel class="text-xs font-normal text-muted-foreground">
            Other Characters
        </DropdownMenuLabel>
        <DropdownMenuGroup>
            <DropdownMenuItem
                v-for="character in otherCharacters"
                :key="character.id"
                class="cursor-pointer"
                @click="switchCharacter(character)"
            >
                <Avatar class="mr-2 h-5 w-5 overflow-hidden rounded">
                    <AvatarImage
                        :src="portraitUrl(character.id)"
                        :alt="character.name"
                    />
                    <AvatarFallback class="rounded text-[10px]">
                        {{ getInitials(character.name) }}
                    </AvatarFallback>
                </Avatar>
                {{ character.name }}
            </DropdownMenuItem>
        </DropdownMenuGroup>
    </template>

    <!-- Actions -->
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <a
                class="block w-full cursor-pointer"
                :href="login.url() + '?add_to_account=1'"
            >
                <Plus class="mr-2 h-4 w-4" />
                Add Character
            </a>
        </DropdownMenuItem>
        <DropdownMenuItem
            v-if="!isLastCharacter"
            class="cursor-pointer text-destructive focus:text-destructive"
            @select.prevent="showRemoveDialog = true"
        >
            <Minus class="mr-2 h-4 w-4" />
            Remove Character
        </DropdownMenuItem>
        <DropdownMenuItem :as-child="true">
            <Link
                class="block w-full cursor-pointer"
                :href="editAppearance()"
                prefetch
            >
                <Settings class="mr-2 h-4 w-4" />
                Settings
            </Link>
        </DropdownMenuItem>
        <DropdownMenuItem :as-child="true">
            <Link
                class="block w-full cursor-pointer"
                :href="logout()"
                @click="handleLogout"
                as="button"
                data-test="logout-button"
            >
                <LogOut class="mr-2 h-4 w-4" />
                Log out
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>

    <AlertDialog v-model:open="showRemoveDialog">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Remove Character</AlertDialogTitle>
                <AlertDialogDescription>
                    Are you sure you want to remove
                    <span class="font-medium text-foreground">{{
                        auth.active_character.name
                    }}</span>
                    from your account?
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Cancel</AlertDialogCancel>
                <AlertDialogAction
                    class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                    @click="confirmRemoveCharacter"
                >
                    Remove
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
