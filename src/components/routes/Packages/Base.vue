<template>
    <main-layout>

        <section :class="{ 'package-tools': true, 'package-tools--search': showSearch }">
            <button class="package-tools__button widget-button widget-button--update" :disabled="totalChanges > 0 || uploading" @click="updatePackages">{{ 'ui.packages.updateButton' | translate }}</button>
            <button class="package-tools__button widget-button widget-button--search" :disabled="uploading" @click="startSearch">{{ 'ui.packages.searchButton' | translate }}</button>
            <slot name="search"/>
            <button class="package-tools__button package-tools__button--upload widget-button" :disabled="!uploads || uploading" :title="uploadError" @click.prevent="$emit('start-upload')">{{ 'ui.packages.uploadButton' | translate }}</button>
        </section>

        <slot/>

        <div :class="{ 'package-actions': true, 'package-actions--active': !!$slots.actions }">
            <slot name="actions"/>
        </div>

    </main-layout>
</template>

<script>
    import { mapState, mapGetters } from 'vuex';
    import routes from '../../../router/routes';

    import MainLayout from '../../layouts/Main';

    export default {
        components: { MainLayout },

        props: {
            showSearch: Boolean,
        },

        computed: {
            ...mapGetters('packages', ['totalChanges']),
            ...mapState('packages/uploads', ['uploads', 'uploading']),

            uploadError: vm => vm.uploads === false ? vm.$t('ui.packages.uploadUnsupported') : '',
        },

        methods: {
            updatePackages() {
                this.$store.commit('packages/updateAll');
            },

            startSearch() {
                this.$router.push(routes.packagesSearch);
            },
        },
    };
</script>


<style rel="stylesheet/scss" lang="scss">
    @import "../../../assets/styles/defaults";

    .package-tools {
        position: relative;
        margin-bottom: 40px;
        text-align: center;

        &__button {
            &.widget-button {
                height: 38px;
                margin-bottom: 10px;
                line-height: 36px;
                border: 1px solid $border-color;
            }

            &--upload:before {
                position: relative;
                display: inline-block;
                top: 4px;
                width: 18px;
                height: 18px;
                margin-right: 8px;
                background: center center no-repeat;
                background-size: 22px 22px;
                content: "";
                background-image: url('../../../assets/images/button-add.svg');
            }
        }

        &__cancel {
            display: none;
            position: absolute;
            top: 48px;
            right: 0;
            width: 38px;
            height: 38px;
            margin: 0;
            padding: 7px;
            color: $text-color;
            border: none;
            background: none;

            @include screen(1024) {
                top: 0;
            }
        }

        &__search {
            display: none;
            margin-bottom: 10px;
            padding-right: 40px;
        }

        &--search {
            .package-tools {
                &__search {
                    display: block;
                }

                &__button--search {
                    display: none;

                    @include screen(1024) {
                        display: inline-block;
                    }
                }

                &__cancel {
                    display: block;
                }
            }
        }

        @include screen(1024) {
            &__button.widget-button {
                width: 180px;
                margin: 0 15px;
            }

            &__search {
                position: absolute !important;
                top: 0;
                right: 0;
                width: 50% !important;
            }
        }
    }

    .package-actions {
        position: fixed;
        overflow: hidden;
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
            align-items: baseline;

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
            display: none;

            @include screen(600) {
                display: inline;
                margin: 0 8px;
                font-weight: $font-weight-bold;
            }
        }

        &__button {
            display: block;
            padding: 0 15px !important;
            margin: 8px;

            @include screen(600) {
                width: auto !important;
            }
        }
    }

</style>
