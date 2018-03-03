<template>
    <div class="package-search">
        <div v-if="packages === false" class="package-search__status package-search__status--offline">
            <p class="package-search__title">{{ 'ui.packagesearch.offline' | translate }}</p>
            <p class="package-search__explain">{{ 'ui.packagesearch.offlineExplain' | translate }}</p>
        </div>
        <div v-else-if="packages === null && !query" class="package-search__status package-search__status--start">
            <p class="package-search__title">{{ 'ui.packagesearch.start' | translate }}</p>
        </div>
        <loader v-else-if="packages === null" class="package-search__status package-search__status--loader">
            <p class="package-search__title" v-html="$t('ui.packagesearch.searching', { query })"></p>
        </loader>
        <div v-else-if="!Object.keys(packages).length" class="package-search__status package-search__status--empty">
            <p class="package-search__title" v-html="$t('ui.packagesearch.empty', { query })"></p>
        </div>

        <package v-for="item in packages" :package="item" :key="item.name"/>

        <a href="https://www.algolia.com/" target="_blank" class="package-search__algolia"><img src="../../assets/images/search-by-algolia.svg" width="200"></a>
    </div>
</template>

<script>
    /* eslint-disable no-param-reassign */

    import Package from './Package';
    import Loader from '../fragments/Loader';

    export default {
        components: { Package, Loader },

        props: ['searchField'],

        data: () => ({
            packages: null,
            previousRequest: null,
            algolia: null,
        }),

        computed: {
            query() {
                return this.$route.query.q;
            },
        },

        watch: {
            query(value) {
                this.searchPackages(this, value);
            },
        },

        methods: {
            searchPackages(vm, value) {
                if (!value) {
                    vm.originals = null;
                    vm.changes = null;
                    vm.packages = null;
                    return;
                }

                vm.algolia.search(value, (err, content) => {
                    if (err) {
                        vm.packages = false;
                    } else if (content.nbHits === 0) {
                        vm.packages = {};
                    } else {
                        vm.packages = {};

                        content.hits.forEach((pkg) => {
                            vm.packages[pkg.name] = pkg;
                        });
                    }
                });
            },
        },

        mounted() {
            this.$store.dispatch('packages/load');

            if (this.searchField) {
                this.searchField.focus();
            }

            if (window.algoliasearch) {
                this.algolia = window.algoliasearch('60DW2LJW0P', 'e6efbab031852e115032f89065b3ab9f').initIndex('v1');
            } else {
                this.packages = false;
            }
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "../../assets/styles/defaults";

    .package-search {
        position: relative;

        &__status {
            margin: 100px 0;
            text-align: center;
            font-size: 20px;
            line-height: 1.5em;

            &--start {
                padding-top: 140px;
                background: url('../../assets/images/search.svg') top center no-repeat;
                background-size: 100px 100px;
            }

            &--empty {
                padding-top: 140px;
                background: url('../../assets/images/sad.svg') top center no-repeat;
                background-size: 100px 100px;
            }

            &--offline {
                padding-top: 140px;
                background: url('../../assets/images/offline.svg') top center no-repeat;
                background-size: 100px 100px;
            }

            &--loader {
                .sk-circle {
                    width: 100px;
                    height: 100px;
                    margin: 0 auto 40px;
                }
            }
        }

        &__explain {
            font-size: 16px;
        }

        &__algolia {
            display: block;
            margin: 50px 0 0;
            text-align: center;
        }
    }
</style>
