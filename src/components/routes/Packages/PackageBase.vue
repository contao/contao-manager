<template>
    <main-layout>
        <section class="package-tools" v-if="isGranted(scopes.UPDATE)">
            <slot name="search">
                <button class="package-tools__button widget-button widget-button--update" :disabled="totalChanges > 0 || uploading" @click="updateAll">{{ $t('ui.packages.updateButton') }}</button>
                <button class="package-tools__button widget-button widget-button--upload" :disabled="!uploads || uploading || !isGranted(scopes.INSTALL)" :title="uploadError" @click.prevent="$emit('start-upload')">{{ $t('ui.packages.uploadButton') }}</button>
            </slot>
        </section>

        <slot />

        <div :class="{ 'package-actions': true, 'package-actions--active': !slotEmpty($slots.actions) }">
            <slot name="actions" />
        </div>
    </main-layout>
</template>

<script>
import { mapState, mapGetters, mapActions } from 'vuex';
import scopes from '../../../scopes';
import slotEmpty from 'contao-package-list/src/filters/slotEmpty';
import MainLayout from '../../layouts/MainLayout';

export default {
    components: { MainLayout },

    computed: {
        ...mapGetters('auth', ['isGranted']),
        ...mapGetters('packages', ['totalChanges']),
        ...mapState('packages/uploads', ['uploads', 'uploading']),
        scopes: () => scopes,

        uploadError: (vm) => {
            if (vm.uploads === false) {
                return vm.$t('ui.packages.uploadUnsupported');
            }

            if (!vm.isGranted(scopes.INSTALL)) {
                return vm.$t('ui.error.permission');
            }

            return '';
        },
    },

    methods: {
        slotEmpty,

        ...mapActions('packages', ['updateAll']),
    },
};
</script>

<style rel="stylesheet/scss" lang="scss">
@use "~contao-package-list/src/assets/styles/defaults";

.package-tools {
    position: relative;
    clear: both;
    text-align: center;

    @include defaults.screen(800) {
        margin-bottom: 40px;
    }

    &__button {
        &.widget-button {
            margin-bottom: 10px;
        }
    }

    @include defaults.screen(800) {
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
    background: rgba(0, 0, 0, 0.8);
    color: #fff;
    transition: max-height 0.4s ease;
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

        @include defaults.screen(1024) {
            max-width: 976px;
            margin: 0 auto;
            padding-left: 0;
            padding-right: 0;
        }

        @include defaults.screen(1200) {
            max-width: 1196px;
        }
    }

    &__text {
        flex-grow: 1;
        display: initial;
        margin: 0 8px;
        font-weight: defaults.$font-weight-bold;
    }

    &__button {
        display: block;
        padding: 0 15px !important;
        margin: 8px;

        &--dryRun {
            width: auto !important;
            flex-grow: 1;
        }

        @include defaults.screen(600) {
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

        @include defaults.screen(600) {
            width: auto !important;
        }
    }
}
</style>
