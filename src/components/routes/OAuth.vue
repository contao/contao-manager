<template>
    <boxed-layout slotClass="view-oauth">
        <header class="view-oauth__header">
            <img src="../../assets/images/logo.svg" width="80" height="80" alt="Contao Logo" />
            <p class="view-oauth__product">Contao Manager</p>
        </header>
        <main class="view-oauth__form">
            <h1 class="view-oauth__headline">{{ 'ui.oauth.headline' | translate }}</h1>
            <p class="view-oauth__description">{{ 'ui.oauth.description' | translate }}</p>
            <p class="view-oauth__client">{{ clientId }}</p>

            <loading-button class="view-oauth__button" color="primary" :disabled="!valid" :loading="authenticating" @click="allowAccess">
                {{ $t('ui.oauth.allow') }}
            </loading-button>
            <button class="view-oauth__button widget-button" @click.prevent="denyAccess" :disabled="!valid || authenticating">
                {{ 'ui.oauth.deny' | translate }}
            </button>
        </main>
    </boxed-layout>
</template>

<script>
    import Vue from 'vue';

    import BoxedLayout from '../layouts/Boxed';
    import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';

    export default {
        components: { BoxedLayout, LoadingButton },

        data: () => ({
            valid: false,
            authenticating: false,
        }),

        computed: {
            clientId() {
                return this.$route.query.client_id;
            },

            scope() {
                return this.$route.query.scope;
            },

            returnUrl() {
                return this.$route.query.return_url;
            },
        },

        methods: {
            allowAccess() {
                this.authenticating = true;

                return Vue.http.post(
                    `api/users/${this.$store.state.auth.username}/tokens`,
                    {
                        client_id: this.clientId,
                        scope: this.scope,
                    },
                ).then((response) => {
                    if (this.returnUrl.includes('?')) {
                        document.location.href = `${this.returnUrl}&token=${response.body.token}`;
                    } else {
                        document.location.href = `${this.returnUrl}?token=${response.body.token}`;
                    }
                });
            },

            denyAccess() {
                document.location.href = this.returnUrl;
            },
        },

        mounted() {
            this.valid = this.clientId && this.returnUrl && this.scope === 'admin';

            if (!this.valid) {
                this.$store.commit('setError', {
                    title: this.$t('ui.oauth.error'),
                    type: 'about:blank',
                    status: 400,
                });
            }
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

        &__button {
            margin-top: 20px;

            .sk-circle {
                color: #fff;
                text-align: center;
            }
        }
    }
</style>
