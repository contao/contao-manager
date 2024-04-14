<template>
    <main-layout>

        <rebuild-cache/>
        <database-status/>
        <maintenance-mode/>
        <install-tool/>
        <debug-mode/>
        <dump-autoload/>
        <composer-install/>
        <composer-cache/>
        <opcode-cache/>

    </main-layout>
</template>

<script>
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
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "~contao-package-list/src/assets/styles/defaults";

    // ToDo:
    .maintenance {
        margin-bottom: 14px;
        background: var(--tiles-bg);
        border-radius: var(--border-radius);

        &__inside {
            &:after {
                display: table;
                clear: both;
                content: "";
            }

            padding: 10px 20px 20px;

            @include screen(1024) {
                padding: 25px 20px;
            }
        }

        &__image {
            display: none;

            img {
                width: 100%;
                height: 100%;
            }

            @include screen(1024) {
                display: block;
                float: left;
                width: 90px;
                height: 90px;
                margin-right: 20px;
            }
        }

        &__about {
            margin-bottom: 20px;

            @include screen(1024) {
                float: left;
                width: 510px;
                margin-bottom: 0;
            }

            h1 {
                position: relative;
                margin-bottom: 5px;
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
            margin-left: .5em;
            padding: 2px 8px;
            font-size: 14px;
            line-height: 1em;
            font-weight: $font-weight-normal;
            background: var(--btn-warning);
            color: #fff;
        }

        &__error {
            background: var(--btn-alert);
        }

        &__actions {
            @include screen(600) {
                margin: 0 -10px;
            }

            @include screen(1024) {
                float: right;
                width: 280px;
                margin: 0 0 0 40px;
            }

            > button,
            > .button-group {
                width: 100%;
                margin-bottom: 10px;

                @include screen(600) {
                    float: right;
                    width: calc(50% - 20px);
                    margin: 0 10px;
                }

                @include screen(1024) {
                    width: 100%;
                    margin: 0 0 10px;
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
