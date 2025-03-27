<template>
    <popup-overlay class="invite-user" :headline="$t('ui.user-manager.inviteHeadline')" @submit="submit" @clear="close">
        <template v-if="token">
            <div class="invite-user__check">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                    <path d="M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2M12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4M11,16.5L6.5,12L7.91,10.59L11,13.67L16.59,8.09L18,9.5L11,16.5Z" />
                </svg>
            </div>

            <i18n-t tag="p" keypath="ui.user-manager.inviteSuccess1" class="invite-user__text">
                <template #expires>{{ datimFormat(token.expires * 1000, 'short', 'long') }}</template>
            </i18n-t>

            <p class="invite-user__text">{{ $t('ui.user-manager.inviteSuccess2') }}</p>
            <code class="invite-user__url">{{ token.url }}</code>
            <button
                type="button"
                class="widget-button widget-button--small invite-user__clipboard"
                :class="{ 'widget-button--clipboard': !copied, 'widget-button--check': copied }"
                v-clipboard="token.url"
                @click="markCopy"
            >{{ $t('ui.user-manager.clipboard') }}</button>
        </template>
        <template v-else>
            <p class="invite-user__text">{{ $t('ui.user-manager.inviteText') }}</p>
            <user-scope v-model="scope" />
            <a class="invite-user__help" :href="`https://to.contao.org/docs/manager-users?lang=${$i18n.locale}`" target="_blank">{{ $t('ui.user-manager.permissions') }}</a>
        </template>
        <template #actions>
            <button type="button" class="widget-button" :disabled="loading" @click="close">{{ token ? $t('ui.user-manager.close') : $t('ui.user-manager.cancel') }}</button>
            <loading-button submit color="primary" :loading="loading" v-if="!token">{{ $t('ui.user-manager.createInvitation') }}</loading-button>
        </template>
    </popup-overlay>
</template>

<script>
import datimFormat from 'contao-package-list/src/filters/datimFormat';
import PopupOverlay from 'contao-package-list/src/components/fragments/PopupOverlay';
import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';
import UserScope from './UserScope';

export default {
    components: { PopupOverlay, LoadingButton, UserScope },

    data: () => ({
        loading: false,
        scope: 'admin',
        token: null,
        copied: false,
    }),

    methods: {
        datimFormat,

        markCopy() {
            this.copied = true;

            setTimeout(() => {
                this.copied = false;
            }, 1000);
        },

        async submit() {
            this.loading = true;

            this.$request.post('api/invitations', { scope: this.scope }, null, {
                201: (response) => {
                    this.token = response.data;
                    this.loading = false;
                },
            });
        },

        close() {
            this.$store.commit('modals/close', 'invite-user');
        },
    },
};
</script>

<style rel="stylesheet/scss" lang="scss">
@use "~contao-package-list/src/assets/styles/defaults";

.invite-user {
    &__check {
        text-align: center;

        svg {
            width: 60px;
            height: 60px;
            fill: var(--btn-primary);
        }
    }

    &__check,
    &__text {
        margin-bottom: 1em;
    }

    &__url {
        display: block;
        margin-top: 2em;
    }

    &__help {
        display: block;
        margin-top: 1em;
        font-size: 12px;
    }

    &__clipboard {
        margin: 1em 0;
    }
}
</style>
