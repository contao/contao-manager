<template>
    <main-layout>

        <section class="package-tools">
            <slot name="search">
                <button class="package-tools__button widget-button widget-button--update" :disabled="totalChanges > 0 || uploading" @click="updateAll">{{ $t('ui.packages.updateButton') }}</button>
                <button class="package-tools__button widget-button widget-button--upload" :disabled="!uploads || uploading" :title="uploadError" @click.prevent="$emit('start-upload')">{{ $t('ui.packages.uploadButton') }}</button>
            </slot>
        </section>

        <slot/>

        <div :class="{ 'package-actions': true, 'package-actions--active': !!$slots.actions }">
            <slot name="actions"/>
        </div>

    </main-layout>
</template>

<script>
    import { mapState, mapGetters, mapActions } from 'vuex';

    import MainLayout from '../../layouts/MainLayout';

    export default {
        components: { MainLayout },

        computed: {
            ...mapGetters('packages', ['totalChanges']),
            ...mapState('packages/uploads', ['uploads', 'uploading']),

            uploadError: vm => vm.uploads === false ? vm.$t('ui.packages.uploadUnsupported') : '',
        },

        methods: {
            ...mapActions('packages', ['updateAll']),
        },
    };
</script>


<style rel="stylesheet/scss" lang="scss">
    @import "~contao-package-list/src/assets/styles/defaults";

    .package-tools {
        position: relative;
        clear: both;
        text-align: center;

        @include screen(800) {
            margin-bottom: 40px;
        }

        &__button {
            &.widget-button {
                margin-bottom: 10px;
            }
        }

        @include screen(800) {
            display: flex;
            justify-content: center;
            align-items: center;

            &__button.widget-button {
                width: auto;
                margin: 0 15px;
                padding: 0 15px;
            }
        }
    }

    .package-actions {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        max-height: 0;
        background: #000;
        background: rgba(0,0,0, 0.8);
        color: #fff;
        transition: max-height .4s ease;
        z-index: 100;

        &--active {
            max-height: 200px;
        }

        &__inner {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            align-items: center;

            margin: 0;
            padding: 12px;
            text-align: right;

            @include screen(1024) {
                max-width: 976px;
                margin: 0 auto;
                padding-left: 0;
                padding-right: 0;
            }

            @include screen(1200) {
                max-width: 1196px;
            }
        }

        &__text {
            flex-grow: 1;
            display: initial;
            margin: 0 8px;
            font-weight: $font-weight-bold;
        }

        &__button {
            display: block;
            padding: 0 15px !important;
            margin: 8px;

            &--dryRun {
                width: auto !important;
                flex-grow: 1;
            }

            @include screen(600) {
                width: auto !important;

                &--dryRun {
                    flex-grow: 0;
                }
            }
        }

        &__button-group {
            display: block;
            width: 100%;
            margin: 8px;

            > .button-group__primary {
                padding: 0 15px !important;
            }

            @include screen(600) {
                width: auto !important;
            }
        }
    }

</style>
