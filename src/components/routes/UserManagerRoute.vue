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
                        <div class="user-manager__you" v-if="currentUser === user.username">{{ $t('ui.user-manager.you') }}</div>
                        <div class="user-manager__username">{{ user.username }}</div>
                        <user-scope readonly label="Permissions" :model-value="user.scope" class="user-manager__scope"/>
                        <div class="user-manager__spacer"></div>

                        <button class="widget-button" @click="changePassword" v-if="currentUser === user.username && !hasPasskey">{{ $t('ui.user-manager.changePassword') }}</button>
                        <button class="widget-button" @click="setupTotp" v-if="currentUser === user.username && !hasPasskey && !hasTotp">{{ $t('ui.user-manager.setupTotp') }}</button>
                        <button class="widget-button" @click="disableTotp" v-if="currentUser === user.username && !hasPasskey && hasTotp">{{ $t('ui.user-manager.disableTotp') }}</button>
                        <button class="widget-button widget-button--alert widget-button--trash" @click="deleteUser(user.username)" v-if="currentUser !== user.username">{{ $t('ui.user-manager.delete') }}</button>
                    </div>
                </template>
            </div>

            <div class="user-manager__new">
                <button class="widget-button widget-button--inline widget-button--add" @click="addUser" v-if="isGranted(scopes.ADMIN)">{{ $t('ui.user-manager.invite') }}</button>
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
import ChangePassword from './Users/ChangePassword.vue';

export default {
    components: { MainLayout, LoadingSpinner, UserScope },

    data: () => ({
        users: null,
    }),

    computed: {
        ...mapState('auth', { currentUser: 'username', hasTotp: 'totpEnabled', hasPasskey: 'passkey' }),
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
            this.$store.commit('modals/open', { id: 'change-password', component: ChangePassword });
        },

        setupTotp() {
            this.$store.commit('modals/open', { id: 'setup-totp', component: SetupTotp });
        },

        disableTotp() {
            this.$store.commit('modals/open', { id: 'disable-totp', component: DisableTotp });
        },

        addUser() {
            this.$store.commit('modals/open', { id: 'invite-user', component: InviteUser });
        },

        async deleteUser(username) {
            if (confirm(this.$t('ui.user-manager.deleteConfirm', { username }))) {
                await this.$request.delete(`api/users/${ username }`);
                this.$notify.success(this.$t('ui.user-manager.deleted'));
                this.load();
            }
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
    &__loading {
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
        grid-template: 1fr / 1fr;
        gap: 20px;

        @include defaults.screen(600) {
            grid-template: 1fr / repeat(2, 1fr);
        }

        @include defaults.screen(800) {
            grid-template: 1fr / repeat(3, 1fr);
        }

        @include defaults.screen(1200) {
            grid-template: 1fr / repeat(4, 1fr);
        }
    }

    &__item {
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        padding: 16px;
        background: var(--tiles-bg);
        border: 1px solid 1px solid var(--tiles-bdr);
        border-radius: 14px;
    }

    &__you {
        position: absolute;
        top: 15px;
        right: -25px;
        width: 100px;
        color: #fff;
        font-weight: defaults.$font-weight-bold;
        line-height: 1.5;
        text-align: center;
        background: var(--btn-primary);
        transform-origin: center center;
        transform: rotateZ(45deg);
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
