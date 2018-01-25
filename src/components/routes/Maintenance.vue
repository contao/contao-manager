<template>
    <main-layout>

        <rebuild-cache/>
        <dump-autoload/>
        <debug-mode/>

    </main-layout>
</template>

<script>
    import MainLayout from '../layouts/Main';
    import RebuildCache from './maintenance/RebuildCache';
    import DumpAutoload from './maintenance/DumpAutoload';

    export default {
        components: { MainLayout, RebuildCache, DumpAutoload },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "../../assets/styles/defaults";

    .maintenance {
        margin-bottom: 14px;
        background: #fff;
        border-bottom: 3px solid #ddd3bc;
        border-radius: 2px;

        &__hint {
            position: relative;
            padding: 8px 150px 8px 56px;
            font-weight: $font-weight-medium;
            font-size: 12px;
            line-height: 1.8;
            border-radius: 2px 2px 0 0;
            background: #e8c8bc url('../../assets/images/hint.svg') 20px 5px no-repeat;
            background-size: 28px 28px;
        }

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

        &__about {
            margin-bottom: 20px;

            @include screen(1024) {
                float: left;
                width: 390px;
                margin-bottom: 0;
            }

            @include screen(1180) {
                width: 610px;
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

        &__config {
            text-align: right;
            margin-bottom: 10px;

            @include screen(600) {
                float: left;
                width: 60%;
            }

            @include screen(1024) {
                width: 280px;
                margin-left: 40px;
                margin-bottom: 0;
            }
        }

        &__actions {
            @include screen(600) {
                float: right;
                width: 40%;
                max-width: 500px;
                padding-left: 40px;
                text-align: right;
            }

            @include screen(1024) {
                width: 160px;
                margin-left: 40px;
                padding-left: 0;
            }

            button {
                width: 100%;

                @include screen(600) {
                    display: inline-block;
                    width: 160px;
                }

                @include screen(1024) {
                    width: 100%;
                    margin-bottom: 10px;
                }
            }
        }
    }
</style>
