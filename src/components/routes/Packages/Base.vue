<template>
    <main-layout>

        <section :class="{ 'package-tools': true, 'package-tools--search': showSearch }">
            <button class="package-tools__button package-tools__button--update widget-button" :disabled="totalChanges > 0" @click="updatePackages">{{ 'ui.packages.updateButton' | translate }}</button>
            <button class="package-tools__button package-tools__button--search widget-button" @click="startSearch">{{ 'ui.packages.searchButton' | translate }}</button>
            <input class="package-tools__search" ref="search" id="search" type="text" :placeholder="$t('ui.packages.searchPlaceholder')" autocomplete="off" v-model="searchInput" @keypress.esc.prevent="stopSearch" @keyup="search">
            <button class="package-tools__cancel" @click="stopSearch">
                <svg height="24" viewBox="0 0 24 24" width="24" fill="#737373" xmlns="http://www.w3.org/2000/svg"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/><path d="M0 0h24v24H0z" fill="none"/></svg>
            </button>
        </section>

        <slot/>

        <div :class="{ 'package-actions': true, 'package-actions--active': !!$slots.actions }">
            <slot name="actions"/>
        </div>

    </main-layout>
</template>

<script>
    import { mapGetters } from 'vuex';
    import routes from '../../../router/routes';

    import MainLayout from '../../layouts/Main';
    import ButtonGroup from '../../widgets/ButtonGroup';

    export default {
        components: { MainLayout, ButtonGroup },

        props: {
            showSearch: Boolean,
        },

        data: () => ({
            searchInput: '',
        }),

        computed: {
            ...mapGetters('packages', ['totalChanges']),
        },

        methods: {
            updatePackages() {
                this.$store.commit('packages/updateAll');
            },

            startSearch() {
                this.$router.push(routes.packagesSearch);
            },

            stopSearch() {
                this.searchInput = '';
                this.$router.push(routes.packages);
            },

            search() {
                if (this.$route.name === routes.packagesSearch.name) {
                    this.$router.push(
                        Object.assign(
                            { query: { q: this.searchInput } },
                            routes.packagesSearch,
                        ),
                    );
                }
            },
        },

        mounted() {
            if (this.showSearch) {
                this.$nextTick(() => {
                    this.$refs.search.focus();
                });
            }
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

            &:before {
                position: relative;
                display: inline-block;
                top: 4px;
                width: 18px;
                height: 18px;
                margin-right: 8px;
                background-size: 22px 22px;
                content: "";
            }

            &--update:before {
                background-image: url('../../../assets/images/button-update.svg');
            }

            &--search:before {
                background-image: url('../../../assets/images/button-search.svg');
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
