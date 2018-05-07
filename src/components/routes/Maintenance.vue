<template>
    <main-layout>

        <rebuild-cache/>
        <debug-mode/>
        <dump-autoload/>
        <composer-install/>
        <composer-cache/>
        <opcode-cache/>

    </main-layout>
</template>

<script>
    import MainLayout from '../layouts/Main';
    import RebuildCache from './maintenance/RebuildCache';
    import DumpAutoload from './maintenance/DumpAutoload';
    import DebugMode from './maintenance/DebugMode';
    import ComposerInstall from './maintenance/ComposerInstall';
    import ComposerCache from './maintenance/ComposerCache';
    import OpcodeCache from './maintenance/OpcodeCache';

    export default {
        components: {
            ComposerInstall,
            ComposerCache,
            MainLayout,
            RebuildCache,
            DumpAutoload,
            DebugMode,
            OpcodeCache,
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "../../assets/styles/defaults";

    .maintenance {
        margin-bottom: 14px;
        background: #fff;
        border-bottom: 3px solid #ddd3bc;
        border-radius: 2px;

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

        &__actions {
            @include screen(600) {
                margin: 0 -10px;
            }

            @include screen(1024) {
                float: right;
                width: 250px;
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

            .link-menu {
                left: auto;
                right: 0;

                &:before {
                    left: auto;
                    right: 17px;
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
