<template>
    <main-layout>
        <rebuild-cache />
        <database-status />
        <maintenance-mode />
        <install-tool />
        <debug-mode />
        <dump-autoload />
        <composer-install />
        <composer-cache />
        <opcode-cache />
    </main-layout>
</template>

<script>
import { mapGetters } from 'vuex';
import scopes from '../../scopes';
import MainLayout from '../layouts/MainLayout';
import DatabaseStatus from './maintenance/DatabaseStatus';
import RebuildCache from './maintenance/RebuildCache';
import InstallTool from './maintenance/InstallTool';
import DebugMode from './maintenance/DebugMode';
import DumpAutoload from './maintenance/DumpAutoload';
import ComposerInstall from './maintenance/ComposerInstall';
import ComposerCache from './maintenance/ComposerCache';
import OpcodeCache from './maintenance/OpcodeCache';
import MaintenanceMode from './maintenance/MaintenanceMode';

export default {
    components: {
        MainLayout,
        DatabaseStatus,
        RebuildCache,
        InstallTool,
        MaintenanceMode,
        DebugMode,
        DumpAutoload,
        ComposerInstall,
        ComposerCache,
        OpcodeCache,
    },

    computed: {
        ...mapGetters('auth', ['isGranted']),
    },

    mounted() {
        if (!this.isGranted(scopes.UPDATE)) {
            this.$router.push('/');
        }
    },
};
</script>

<style rel="stylesheet/scss" lang="scss">
@use "~contao-package-list/src/assets/styles/defaults";

.maintenance {
    margin-bottom: 14px;
    background: var(--tiles-bg);
    border-radius: var(--border-radius);

    &__inside {
        padding: 10px 20px 20px;

        @include defaults.screen(1024) {
            display: grid;
            grid-template: auto / 90px auto 300px;
            column-gap: 20px;
            padding: 25px 20px;
        }
    }

    &__image {
        aspect-ratio: 1;
        display: none;

        img {
            width: 100%;
            height: 100%;
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        @include defaults.screen(1024) {
            display: block;
        }
    }

    &__about {
        margin-bottom: 20px;

        @include defaults.screen(1024) {
            margin-bottom: 0;
        }

        h1 {
            position: relative;
            margin-bottom: 5px;
            display: flex;
            flex-wrap: wrap;
            column-gap: 0.5em;
            align-items: baseline;
        }

        p {
            margin: 0 0 1em;
            display: inline;
        }
    }

    &__warning,
    &__error {
        position: relative;
        top: -2px;
        padding: 4px 8px;
        font-size: 14px;
        line-height: 1em;
        font-weight: defaults.$font-weight-normal;
        background: var(--btn-warning);
        color: var(--clr-btn);
        border-radius: var(--border-radius);
    }

    &__error {
        background: var(--btn-alert);
    }

    &__actions {
        display: flex;
        flex-direction: column;
        row-gap: 10px;
        column-gap: 20px;

        @include defaults.screen(600) {
            flex-direction: row-reverse;
        }

        @include defaults.screen(1024) {
            flex-direction: column;
            margin-left: 20px;
        }

        > button,
        > .button-group {
            width: 100%;

            @include defaults.screen(600) {
                width: calc(50% - 10px);
            }

            @include defaults.screen(1024) {
                width: 100%;
            }
        }
    }

    &__loader {
        width: 50px;
        margin: 0 auto;

        .sk-circle {
            width: 50px;
            height: 50px;
        }
    }
}
</style>
