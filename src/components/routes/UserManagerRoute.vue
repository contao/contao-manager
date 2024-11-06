<template>
    <main-layout>

        <loading-spinner class="log-viewer__status log-viewer__status--loader" v-if="users === null">
            <p class="log-viewer__title">{{ $t('ui.log-viewer.loading') }}</p>
        </loading-spinner>

        <div v-else>

            <div class="user-manager__list">
                <!-- eslint-disable vue/no-v-for-template-key -->
                <template v-for="(user, k) in users" :key="k">
                    <div class="user-manager__item">
                        <div>Username: {{ user.username }}</div>
                        <div>E-Mail: {{ user.email }}</div>
                        <div>Scope: {{ user.scope }}</div>
                        <div class="user-manager__spacer"></div>
                        <button class="widget-button widget-button--alert widget-button--trash" @click="deleteUser(user.username)" v-if="currentUser !== user.username">Delete</button>
                    </div>
                </template>
            </div>

            <div class="user-manager__new">
                <button class="widget-button widget-button--inline widget-button--add" @click="addUser" v-if="isGranted(scopes.ADMIN)">Invite User</button>
            </div>
        </div>

    </main-layout>
</template>

<script>
import { mapState, mapGetters } from 'vuex';
import scopes from '../../scopes';
import MainLayout from '../layouts/MainLayout';
import LoadingSpinner from 'contao-package-list/src/components/fragments/LoadingSpinner';
import InviteUser from './Users/InviteUser';

export default {
    components: { MainLayout, LoadingSpinner },

    computed: {
        ...mapState('auth', { currentUser: 'username' }),
        ...mapGetters('auth', ['isGranted']),
        ...mapGetters('users', ['users']),
        scopes: () => scopes,
    },

    methods: {
        addUser() {
            this.$store.commit('modals/open', { id: 'invite-user', component: InviteUser, });
        },

        async deleteUser(username) {
            await this.$store.dispatch('users/delete', username);
            await this.$store.dispatch('users/get', false);
        }
    },

    async created () {
        await this.$store.dispatch('users/get');
    },
};
</script>

<style rel="stylesheet/scss" lang="scss">
@use "~contao-package-list/src/assets/styles/defaults";

.user-manager {
    &__list {
        display: grid;
        grid-template: minmax(200px, 1fr) / repeat(4, 1fr);
        gap: 20px;
    }

    &__item {
        display: flex;
        flex-direction: column;
        padding: 16px;
        background: var(--tiles-bg);
        border: 1px solid 1px solid var(--tiles-bdr);
        border-radius: 14px;
    }

    &__spacer {
        flex-grow: 1;
    }

    &__new {
        margin-top: 60px;
        text-align: center;
    }
}
</style>
