<template>
    <div class="layout-boxed">
        <div class="layout-boxed__cell">
            <div :class="'layout-boxed__container ' + (wide ? ' layout-boxed__container--wide ' : '') + slotClass">
                <slot></slot>
                <div class="clearfix"></div>
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

    .layout-boxed {
        display: table;
        width: 100%;
        height: 100%;

        &__cell {
            display: table-cell;
            overflow: hidden;
            vertical-align: middle;
            padding: 10px 0;
        }

        &__container {
            position: relative;
            max-width: 380px;
            margin: 0 auto;
            background: #fff;

            &:after {
                content: "";
                display: table;
                clear: both
            }

            &:before {
                position: absolute;
                display: block;
                width: 100px;
                top: 0;
                bottom: 0;
                left: -100px;
                content: "";
                box-shadow: 15px 0 60px 25px #ebe6db inset, -8px 0 8px -8px rgba(0, 0, 0, .8) inset;
            }

            &:after {
                position: absolute;
                display: block;
                width: 100px;
                top: 0;
                bottom: 0;
                right: -100px;
                content: "";
                box-shadow: -15px 0 60px 25px #ebe6db inset, 8px 0 8px -8px rgba(0, 0, 0, .8) inset;
            }
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
