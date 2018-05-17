<template>
    <main-layout>

        <section class="search-bar" slot="subheader">
            <div class="layout-main__content">
                <div class="search-bar__inside">
                    <input class="search-bar__input" ref="search" id="search" type="text" :placeholder="$t('ui.packages.searchPlaceholder')" autocomplete="off" v-model="searchInput" @keypress.esc.prevent="stopSearch" @keyup="search">
                    <button class="search-bar__button search-bar__button--stop" @click="stopSearch" v-if="isSearchMode">Cancel</button>
                    <button class="search-bar__button search-bar__button--start" v-else>Search</button>
                </div>
            </div>
        </section>

        <router-view ref="component" :searchField="$refs.search"/>

        <div :class="{ 'package-actions': true, 'package-actions--active': hasChanges }">
            <div class="package-actions__inner" v-if="$route.name === 'packages'">
                <p class="package-actions__text">{{ 'ui.packages.changesMessage' | translate({ total: totalChanges }, totalChanges) }}</p>
                <button class="package-actions__button widget-button" @click="dryrunChanges">{{ 'ui.packages.changesDryrun' | translate }}</button>
                <button class="package-actions__button widget-button widget-button--primary" @click="applyChanges">{{ 'ui.packages.changesApply' | translate }}</button>
                <button class="package-actions__button widget-button widget-button--alert" @click="resetChanges">{{ 'ui.packages.changesReset' | translate }}</button>
            </div>
            <div class="package-actions__inner" v-else>
                <p class="package-actions__text">{{ 'ui.packages.changesMessage' | translate({ total: totalChanges }, totalChanges) }}</p>
                <router-link :to="packageRoute" class="package-actions__button widget-button widget-button--primary">{{ 'ui.packages.changesReview' | translate }}</router-link>
            </div>
        </div>

    </main-layout>
</template>

<script>
    import { mapGetters } from 'vuex';
    import routes from '../../router/routes';

    import MainLayout from '../layouts/Main';
    import ButtonGroup from '../widgets/ButtonGroup';

    export default {
        components: { MainLayout, ButtonGroup },

        data: () => ({
            searchInput: '',
            packageRoute: routes.packages,
        }),

        computed: {
            hasChanges() {
                return Object.keys(this.$store.state.packages.add).length > 0
                    || Object.keys(this.$store.state.packages.change).length > 0
                    || Object.keys(this.$store.state.packages.update).length > 0
                    || this.$store.state.packages.remove.length > 0;
            },

            isSearchMode() {
                return this.$route.name === routes.packagesSearch.name;
            },

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
                // if (this.$route.name === routes.packagesSearch.name) {
                this.$router.push(
                    Object.assign(
                        { query: { q: this.searchInput } },
                        routes.packagesSearch,
                    ),
                );
                // }
            },

            dryrunChanges() {
                this.$store.dispatch('packages/apply', true);
            },

            applyChanges() {
                this.$store.dispatch('packages/apply').then(() => this.$store.dispatch('load', true));
            },

            resetChanges() {
                this.$store.commit('packages/reset');
            },
        },

        watch: {
            $route(route) {
                if (route.name !== routes.packagesSearch.name) {
                    this.searchInput = '';
                }
            },
        },

        mounted() {
            this.$store.commit('packages/reset');
        },
    };
</script>


<style rel="stylesheet/scss" lang="scss">
    @import "../../assets/styles/defaults";

    .search-bar {
        margin: 30px 0 40px;
        background: #e5dfcf;
        border-top: 1px solid #dcd8cc;
        border-bottom: 1px solid #dcd8cc;

        &__inside {
            position: relative;
            max-width: 600px;
            margin: 0 auto;
            padding: 25px 20px;
        }

        &__input {
            display: block;
            height: 46px !important;
            padding-right: 50px !important;
            line-height: 46px;
            border: none !important;

            &::placeholder {
                color: $text-color;
            }
        }

        &__button {
            position: absolute;
            top: 33px;
            right: 30px;
            width: 32px;
            height: 32px;
            background: none;
            border: none;

            &--start {
                background: url("../../assets/images/search.svg") 0 0 no-repeat;
                background-size: contain;
                text-indent: -999em;
            }

            &--stop {
                background: url("../../assets/images/search-close.svg") 0 0 no-repeat;
                background-size: contain;
                text-indent: -999em;
            }
        }
    }

    .package-tools {
        position: relative;
        margin: 40px 5px 20px;
        text-align: right;

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
        }

        &__cancel {
            display: none;
            position: absolute;
            top: 48px;
            right: 10px;
            width: 38px;
            height: 38px;
            margin: 0;
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
        height: 0;
        background: #000;
        background: rgba(0,0,0, 0.8);
        color: #fff;
        transition: height .4s ease;
        z-index: 100;

        &--active {
            height: 80px;
        }

        &__inner {
            margin: 0 20px;
            padding: 20px 0;
            text-align: right;

            @include screen(1024) {
                max-width: 960px;
                margin: 0 auto;
            }

            @include screen(1200) {
                max-width: 1180px;
            }
        }

        &__text {
            display: none;

            @include screen(600) {
                display: inline;
                font-weight: $font-weight-bold;
            }
        }

        &__button {
            width: calc(50% - 10px) !important;
            padding: 0 15px !important;

            &:last-child {
                margin-left: 16px;
            }

            @include screen(600) {
                width: auto !important;
                margin-left: 16px;
            }
        }
    }

</style>
