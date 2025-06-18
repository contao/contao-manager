<template>
    <div class="view-error">
        <button type="button" class="view-error__close" @click="close" v-if="isDebug">
            <svg height="24" viewBox="0 0 24 24" width="24" fill="#fff" xmlns="http://www.w3.org/2000/svg">
                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                <path d="M0 0h24v24H0z" fill="none" />
            </svg>
        </button>
        <div class="view-error__content" ref="debug">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="view-error__icon">
                <path
                    d="M473.7 73.8l-2.4-2.5c-46-47-118-51.7-169.6-14.8L336 159.9l-96 64 48 128-144-144 96-64-28.6-86.5C159.7 19.6 87 24 40.7 71.4l-2.4 2.4C-10.4 123.6-12.5 202.9 31 256l212.1 218.6c7.1 7.3 18.6 7.3 25.7 0L481 255.9c43.5-53 41.4-132.3-7.3-182.1z"
                />
            </svg>
            <div class="view-error__status">
                ERROR <a :href="`https://developer.mozilla.org/${$i18n.locale}/docs/Web/HTTP/Status/${error.status}`" target="_blank" v-if="error.status"> {{ error.status }}</a>
            </div>
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

        response: (vm) => vm.error.response,

        isDebug: () => process.env.NODE_ENV === 'development',

        title() {
            if (this.error.title) {
                return this.error.title;
            }

            if (this.response) {
                return this.$t('ui.error.title', {
                    method: (this.response.config.headers['X-HTTP-Method-Override'] || this.response.config.method).toUpperCase(),
                    url: this.response.config.url,
                });
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

            if (this.response) {
                return this.$t('ui.error.response', this.error);
            }

            return '';
        },

        debug() {
            if (this.error.debug) {
                return this.error.debug;
            }

            if (this.response && this.response.data) {
                return this.response.data;
            }

            return '';
        },
    },

    methods: {
        close() {
            this.$store.commit('setError', null);
        },
    },

    mounted() {
        if (this.debug && this.debug.includes('window.Sfdump')) {
            this.$refs.debug.innerHTML = '';
            this.$refs.debug.classList.add('view-error__content--dump');
            this.$refs.debug.append(document.createRange().createContextualFragment(this.debug));
        }
    },
};
</script>

<style rel="stylesheet/scss" lang="scss">
@use '~contao-package-list/src/assets/styles/defaults';

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
    font-family: defaults.$font-monospace;
    font-size: 13px;
    line-height: 1.2;
    background-color: rgba(0, 0, 0, 0.85098);
    background-position: initial;
    background-repeat: initial;
    z-index: 9998;

    &__close {
        position: absolute;
        top: 15px;
        right: 10px;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 35px;
        height: 35px;
        background: none;
        border: none;
        cursor: pointer;
        z-index: 10;

        &:hover {
            border: 1px solid #fff;
        }
    }

    &__content {
        display: flex;
        flex-direction: column;
        align-items: center;
        max-width: 800px;
        max-height: 100vh;
        line-height: 1.5;
        text-align: center;

        &--dump {
            text-align: left !important;
            overflow: auto !important;
            max-width: none !important;
            width: 100vw !important;
            height: calc(100vh - 40px) !important;
            display: block !important;
            z-index: -1;

            .sf-dump {
                background: none !important;
            }
        }
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
        background-color: #e36049;
        border-radius: var(--border-radius);
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
        width: 100%;
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
