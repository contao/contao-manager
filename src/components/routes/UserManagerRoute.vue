<template>
    <main-layout class="user-manager">

        <loading-spinner class="user-manager__loading" v-if="users === null">
            <p>{{ $t('ui.user-manager.loading') }}</p>
        </loading-spinner>

        <div v-else>
            <div class="user-manager__list">
                <!-- eslint-disable vue/no-v-for-template-key -->
                <template v-for="(user, k) in users" :key="k">
                    <div class="user-manager__item">
                        <div class="user-manager__username">{{ user.username }}</div>
                        <user-scope readonly label="Permissions" :model-value="user.scope" class="user-manager__scope"/>
                        <div class="user-manager__spacer"></div>

                        <button class="widget-button" @click="changePassword" v-if="currentUser === user.username">Change Password</button>
                        <button class="widget-button" @click="setupTotp" v-if="currentUser === user.username && !hasTotp">Setup Two-Factor Authentication</button>
                        <button class="widget-button" @click="disableTotp" v-if="currentUser === user.username && hasTotp">Disable Two-Factor Authentication</button>
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
import UserScope from './Users/UserScope.vue';
import InviteUser from './Users/InviteUser';
import SetupTotp from './Users/SetupTotp.vue';
import DisableTotp from './Users/DisableTotp.vue';

export default {
    components: { MainLayout, LoadingSpinner, UserScope },

    data: () => ({
        users: null,
    }),

    computed: {
        ...mapState('auth', { currentUser: 'username', hasTotp: 'totpEnabled' }),
        ...mapGetters('auth', ['isGranted']),
        scopes: () => scopes,
    },

    methods: {
        load () {
            this.$request.get('api/users', null, {
                200: (response) => {
                    this.users = response.data
                }
            });
        },

        changePassword() {

        },

        setupTotp() {
            this.$store.commit('modals/open', { id: 'setup-totp', component: SetupTotp, });
        },

        disableTotp() {
            this.$store.commit('modals/open', { id: 'disable-totp', component: DisableTotp, });
        },

        addUser() {
            this.$store.commit('modals/open', { id: 'invite-user', component: InviteUser, });
        },

        async deleteUser(username) {
            await this.$request.delete(`api/users/${username}`);
            this.load();
        }
    },

    async mounted () {
        this.load();
    },
};
</script>

<style rel="stylesheet/scss" lang="scss">
@use "~contao-package-list/src/assets/styles/defaults";

.user-manager {
    &__status {
        margin: 100px 0;
        text-align: center;
        font-size: 20px;
        line-height: 1.5em;

        .sk-circle {
            width: 100px;
            height: 100px;
            margin: 0 auto 40px;
        }
    }

    &__list {
        display: grid;
        grid-template: 1fr / repeat(4, 1fr);
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

    &__new {
        margin-top: 60px;
        text-align: center;
    }

    &__spacer {
        flex-grow: 1;
        min-height: 1em;
    }

    &__username {
        font-size: 18px;
        font-weight: defaults.$font-weight-bold;
    }

    &__scope {
        margin-top: 1em;
    }

    .widget-button {
        margin-top: 5px;
    }
}
</style>
