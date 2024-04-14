<template>
    <div class="layout-boxed">
        <div class="layout-boxed__cell">
            <div :class="'layout-boxed__container ' + (wide ? ' layout-boxed__container--wide ' : '') + slotClass">
                <slot></slot>
                <footer-fragment :display="wide ? 'boxed' : ''"></footer-fragment>
            </div>
        </div>
    </div>
</template>

<script>
    import FooterFragment from './../fragments/FooterFragment';

    export default {
        components: { FooterFragment },
        props: {
            slotClass: String,
            wide: Boolean,
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "~contao-package-list/src/assets/styles/defaults";

    .layout-boxed,
    .animate-blur-out,
    .animate-blur-in {
        display: table;
        width: 100%;
        height: 100%;
    }

    .layout-boxed {

        &__cell {
            display: table-cell;
            overflow: hidden;
            vertical-align: middle;
            padding: 10px;
        }

        &__container {
            position: relative;
            max-width: 380px;
            margin: 0 auto;
            background: var(--popup-bg);
            border-radius: 8px;
        }

        @include screen(960) {
            &__container {
                margin-top: 20px;
                margin-bottom: 20px;

                &--wide {
                    max-width: 940px;
                }
            }
        }
    }

</style>
