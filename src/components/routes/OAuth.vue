<template>
    <boxed-layout slotClass="view-oauth">
        <header class="view-oauth__header">
            <img src="../../assets/images/logo.svg" width="80" height="80" alt="Contao Logo" />
            <p class="view-oauth__product">Contao Manager</p>
        </header>
        <main class="view-oauth__form">
            <h1 class="view-oauth__headline">{{ $t('ui.oauth.headline') }}</h1>
            <p class="view-oauth__description">{{ $t('ui.oauth.description') }}</p>
            <p class="view-oauth__client">{{ $route.query.client_id }}</p>
            <p class="view-oauth__domain">
              {{ $t('ui.oauth.domain') }}<br>
              <strong>{{ hostname }}</strong>
            </p>
            <loading-button class="view-oauth__button" color="primary" :disabled="!valid" :loading="authenticating" @click="allowAccess">
                {{ $t('ui.oauth.allow') }}
            </loading-button>
            <button class="view-oauth__button widget-button" @click.prevent="denyAccess" :disabled="!valid || authenticating">
                {{ $t('ui.oauth.deny') }}
            </button>
        </main>
    </boxed-layout>
</template>

<script>
    import BoxedLayout from '../layouts/Boxed';
    import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';

    export default {
        components: { BoxedLayout, LoadingButton },

        data: () => ({
            valid: false,
            authenticating: false,
        }),

        computed: {
          hostname: vm => new URL(vm.$route.query.return_url).hostname,
        },

        methods: {
            async allowAccess() {
                this.authenticating = true;

                try {
                    const response = await this.$http.post(
                        `api/users/${this.$store.state.auth.username}/tokens`,
                        {
                            client_id: this.$route.query.client_id,
                            scope: this.$route.query.scope,
                        },
                    )

                    if (this.$route.query.response_type === undefined) {
                        // Backwards compatibility
                        if (this.$route.query.return_url.includes('?')) {
                            document.location.href = `${this.$route.query.return_url}&token=${response.body.token}`;
                        } else {
                            document.location.href = `${this.$route.query.return_url}?token=${response.body.token}`;
                        }
                        return
                    }

                    // OAuth Implicit Grant (RFC 6749 section 4.2)
                    this.redirect({
                        access_token: response.body.token,
                        token_type: 'bearer',
                        scope: this.$route.query.scope,
                        endpoint: `${location.origin}${location.pathname}`
                    });
                } catch (err) {
                    this.redirect({ error: 'server_error' })
                }
            },

            denyAccess() {
                if (this.$route.query.response_type === 'token') {
                    this.redirect({ error: 'access_denied' })
                } else {
                    document.location.href = this.$route.query.return_url;
                }
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
            }
        },

        mounted() {
            // Backwards compatibility
            if (this.$route.query.response_type === undefined) {
                if (!this.$route.query.client_id || !this.$route.query.return_url || this.$route.query.scope !== 'admin') {
                    this.$store.commit('setError', {
                        title: this.$t('ui.oauth.error'),
                        type: 'about:blank',
                        status: 400,
                    });
                } else {
                    this.valid = true
                }

                return
            }

            const redirectUri = new URL(this.$route.query.redirect_uri);

            if (redirectUri.protocol !== 'https:' && redirectUri.hostname !== 'localhost') {
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

            if (this.$route.query.scope !== 'admin') {
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
    @import "~contao-package-list/src/assets/styles/defaults";

    .view-oauth {
        &__header {
            max-width: 280px;
            margin: 0 auto 60px;
            padding-top: 40px;
            text-align: center;
        }

        &__product {
            margin-top: 15px;
            font-size: 36px;
            font-weight: $font-weight-light;
            line-height: 1;
        }

        &__form {
            position: relative;
            max-width: 250px;
            margin: 0 auto 80px;
            text-align: center;

            input,
            select {
                margin: 5px 0 10px;
            }
        }

        &__headline {
            margin-bottom: 0;
        }

        &__description {
            margin-top: .5em;
            margin-bottom: .5em;
        }

        &__client {
            margin: 1em 0;
            font-size: 32px;
        }

        &__domain {
          color: $red-button;
          margin-top: .5em;
          margin-bottom: 2em;

          > strong {
            margin-top: .5em;
            display: inline-block;
            background: $red-button;
            color: #fff;
            padding: 5px 10px;
          }
        }

        &__error {
            padding: 10px;
            color: $red-button;
            border: 1px solid $red-button;
        }

        &__button {
            margin-top: 20px;

            .sk-circle {
                color: #fff;
                text-align: center;
            }
        }
    }
</style>
