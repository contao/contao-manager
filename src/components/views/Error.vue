<template>
    <div class="view-error">
        <div>
            <h1 class="view-error__headline">
                <span>ERROR <a :href="'https://httpstatuses.com/'+error.status" target="_blank" v-if="error.status"> {{ error.status }}</a></span>
                {{ error.title }}
            </h1>
            <a :href="error.type" target="_blank" v-if="error.type !== 'about:blank'" class="view-error__link">{{ $t('ui.error.moreLink') }}</a>
            <div class="view-error__details" v-if="error.detail">{{ error.detail }}</div>
        </div>
    </div>
</template>

<script>
    import { mapState } from 'vuex';

    export default {
        computed: mapState(['error']),
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
        overflow: auto;
        padding: 10px;
        color: #e8e8e8;
        font-family: $font-monospace;
        font-size: 13px;
        line-height: 1.2;
        background-color: rgba(0, 0, 0, 0.85098);
        background-position: initial;
        background-repeat: initial;
        z-index: 9998;

        &__headline {
            display: inline;
            font-size: 1em;
            line-height: 1.5;

            span {
                padding: 2px 4px;
                background-color: #E36049;
                border-radius: 2px
            }
        }

        &__link,
        &__headline a {
            color: #e8e8e8;
            text-decoration: underline;
        }

        &__details {
            display: block;
            margin-top: 2em;
            white-space: pre-line;
        }
    }
</style>
