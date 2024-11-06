<template>
    <boxed-layout slotClass="view-oauth">
        <header class="view-oauth__header">
            <img src="../../assets/images/oauth.svg" width="80" height="80" alt="" class="view-oauth__icon"/>
            <p class="view-oauth__product">{{ $t('ui.oauth.headline') }}</p>
        </header>
        <main class="view-oauth__form">
            <p class="view-oauth__description">{{ $t('ui.oauth.description') }}</p>
            <p class="view-oauth__client">{{ hostname }}</p>
            <template v-if="scopes.length">
                <user-scope class="view-oauth__scopes" :allowed="scopes" v-model="scope"/>
                <p class="view-oauth__warning">{{ $t('ui.oauth.domain') }}</p>
                <loading-button class="view-oauth__button" color="primary" :disabled="!valid" :loading="authenticating" @click="allowAccess">
                    {{ $t('ui.oauth.allow') }}
                </loading-button>
                <button class="view-oauth__button widget-button" @click.prevent="denyAccess" :disabled="!valid || authenticating">
                    {{ $t('ui.oauth.deny') }}
                </button>
            </template>
            <template v-else>
                <p class="view-oauth__warning">{{ $t('ui.oauth.outOfScope') }}</p>
                <button class="view-oauth__button widget-button" @click.prevent="denyAccess" :disabled="!valid">
                    {{ $t('ui.oauth.deny') }}
                </button>
                <button class="view-oauth__button widget-button widget-button--anchor" @click.prevent="logout">
                    {{ $t('ui.oauth.switchUser') }}
                </button>
            </template>
        </main>
    </boxed-layout>
</template>

<script>
    import { mapGetters, mapActions } from 'vuex';
    import axios from 'axios';
    import BoxedLayout from '../layouts/BoxedLayout';
    import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';
    import UserScope from './Users/UserScope';

    export default {
        components: { BoxedLayout, LoadingButton, UserScope },

        data: () => ({
            valid: false,
            authenticating: false,
            scope: null,
        }),

        computed: {
            ...mapGetters('auth', ['isGranted']),

            hostname: vm => vm.$route.query.redirect_uri ? new URL(vm.$route.query.redirect_uri).hostname : '???',
            scopes: vm => vm.$route.query.scope.split(' ').filter(scope => vm.isGranted(scope)),
        },

        methods: {
            ...mapActions('auth', ['logout']),

            async allowAccess() {
                this.authenticating = true;

                try {
                    const response = await axios.post(
                        `api/users/${this.$store.state.auth.username}/tokens`,
                        {
                            client_id: this.$route.query.client_id,
                            scope: this.scope,
                        },
                    )

                    // OAuth Implicit Grant (RFC 6749 section 4.2)
                    this.redirect({
                        access_token: response.data.token,
                        token_type: 'bearer',
                        scope: this.scope,
                        endpoint: `${location.origin}${location.pathname}`
                    });
                } catch (err) {
                    this.redirect({ error: 'server_error' })
                }
            },

            denyAccess() {
                this.redirect({ error: 'access_denied' })
            },

            redirect(query) {
                const q = [];
                for (let d in query) {
                    q.push(encodeURIComponent(d) + '=' + encodeURIComponent(query[d]));
                }

                if (this.$route.query.state) {
                    q.push(encodeURIComponent('state') + '=' + encodeURIComponent(this.$route.query.state))
                }

                const params = q.join('&');
                const redirectUrl = this.$route.query.redirect_uri

                if (redirectUrl.includes('#')) {
                    document.location.href = `${redirectUrl}&${params}`;
                } else {
                    document.location.href = `${redirectUrl}#${params}`;
                }
            },
        },

        async mounted() {
            await this.$router.isReady();

            let error = false;
            try {
                const redirectUri = new URL(this.$route.query.redirect_uri);

                if (redirectUri.protocol !== 'https:' && redirectUri.hostname !== 'localhost') {
                    error = true;
                }
            } catch (err) {
                error = true;
            }

            if (error) {
                this.$store.commit('setError', {
                    title: this.$t('ui.oauth.error'),
                    detail: this.$t('ui.oauth.https'),
                    type: 'https://tools.ietf.org/html/rfc6749#section-3.1.2.1',
                    status: 400,
                });
                return;
            }

            if (this.$route.query.response_type !== 'token') {
                return this.redirect({ error: 'unsupported_response_type' })
            }

            if (!this.scopes.length) {
                return this.redirect({ error: 'invalid_scope' })
            }

            if (!this.$route.query.client_id) {
                return this.redirect({ error: 'invalid_request' })
            }

            this.valid = true
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
@use "~contao-package-list/src/assets/styles/defaults";

.view-oauth {
    &__header {
        max-width: 280px;
        margin: 0 auto 40px;
        padding-top: 40px;
        text-align: center;
    }

    &__icon {
        background: var(--contao);
        border-radius: 10px;
        padding:10px;
    }

    &__product {
        margin-top: 15px;
        font-size: 36px;
        font-weight: defaults.$font-weight-light;
        line-height: 1;
    }

    &__form {
        position: relative;
        max-width: 280px;
        margin: 0 auto 60px;
        text-align: center;

        input,
        select {
            margin: 5px 0 10px;
        }
    }

    &__description {
        margin-top: .5em;
        margin-bottom: .5em;
    }

    &__client {
        margin: 1em 0;
        font-size: 32px;
    }

    &__scopes {
        text-align: left;
    }

    &__warning {
      color: var(--btn-alert);
      margin-top: 2em;
      margin-bottom: 2em;
    }

    &__button {
        margin-top: 1em;

        .sk-circle {
            color: #fff;
            text-align: center;
        }
    }
}
</style>
