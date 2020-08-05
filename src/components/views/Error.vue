<template>
    <div class="view-error">
        <div class="view-error__content">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="view-error__icon"><path d="M473.7 73.8l-2.4-2.5c-46-47-118-51.7-169.6-14.8L336 159.9l-96 64 48 128-144-144 96-64-28.6-86.5C159.7 19.6 87 24 40.7 71.4l-2.4 2.4C-10.4 123.6-12.5 202.9 31 256l212.1 218.6c7.1 7.3 18.6 7.3 25.7 0L481 255.9c43.5-53 41.4-132.3-7.3-182.1z"/></svg>
            <div class="view-error__status">ERROR <a :href="'https://httpstatuses.com/'+error.status" target="_blank" v-if="error.status"> {{ error.status }}</a></div>
            <h1 class="view-error__headline">{{ title }}</h1>
            <div class="view-error__details" v-if="detail">{{ detail }}</div>
            <div class="view-error__debug" v-if="debug">{{ debug }}</div>
            <div class="view-error__actions">
                <a :href="error.type" target="_blank" v-if="error.type !== 'about:blank'" class="view-error__link">{{ $t('ui.error.moreLink') }}</a>
                <a :href="`https://to.contao.org/support?lang=${$i18n.locale}`" target="_blank" class="view-error__link view-error__link--support">{{ $t('ui.error.support') }}</a>
            </div>
        </div>
    </div>
</template>

<script>
    import { mapState } from 'vuex';

    export default {
        computed: {
            ...mapState(['error']),

            request: vm => vm.error.request,
            response: vm => vm.error.response,

            title() {
                if (this.error.title) {
                    return this.error.title;
                }

                if (this.request) {
                    return this.$t('ui.error.title', {
                        method: this.request.headers.get('X-HTTP-Method-Override') || this.request.method,
                        url: this.request.url,
                    })
                }

                return this.$t('ui.app.apiError');
            },

            detail() {
                if (this.error.detail) {
                    return this.error.detail;
                }

                if (this.error.debug) {
                    return '';
                }

                if (this.error.status === 500) {
                    return this.$t('ui.error.server500');
                }

                if (this.request) {
                    return this.$t('ui.error.response', this.error);
                }

                return '';
            },

            debug() {
                if (this.error.debug) {
                    return this.error.debug;
                }

                if (this.response && this.response.body) {
                    return this.response.body;
                }

                return '';
            },
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "~contao-package-list/src/assets/styles/defaults";

    .view-error {
        position: fixed;
        left: 0;
        right: 0;
        top: 0;
        bottom: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 10px;
        color: #e8e8e8;
        font-family: $font-monospace;
        font-size: 13px;
        line-height: 1.2;
        background-color: rgba(0, 0, 0, 0.85098);
        background-position: initial;
        background-repeat: initial;
        z-index: 9998;

        &__content {
            display: flex;
            flex-direction: column;
            align-items: center;
            max-width: 800px;
            max-height: 100vh;
            line-height: 1.5;
            text-align: center;
        }

        &__icon {
            display: block;
            height: 100px;
            margin: 2em 0;
            fill: #fff;
        }

        &__status {
            margin-bottom: 1em;
            padding: 2px 4px;
            background-color: #E36049;
            border-radius: 2px;
        }

        &__headline {
            margin: 0;
            font-size: 1em;
            line-height: 1.5;
        }

        &__status a {
            color: #e8e8e8;
            text-decoration: underline;
        }

        &__details {
            display: block;
            margin-top: 2em;
            white-space: pre-line;
        }

        &__debug {
            align-self: flex-start;
            max-height: 60vh;
            overflow-y: auto;
            margin-top: 2em;
            text-align: left;
            white-space: pre-line;
        }

        &__actions {
            margin: 4em 0;
            text-align: center;
        }

        &__link {
            margin: 10px;
            padding: 10px 20px;
            border: 1px solid #fff;
            border-radius: 4px;
            color: #fff;
        }
    }
</style>
