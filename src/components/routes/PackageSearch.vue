<template>
    <package-base show-search>
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

            <search-package v-for="item in packages" :package="item" :key="item.name"/>

            <a href="https://www.algolia.com/" target="_blank" class="package-search__algolia"><img src="../../assets/images/search-by-algolia.svg" width="200"></a>
        </div>

        <div class="package-actions__inner" slot="actions" v-if="totalChanges">
            <p class="package-actions__text">{{ $t('ui.packages.changesMessage', { total: totalChanges }, totalChanges) }}</p>
            <router-link :to="packageRoute" class="package-actions__button widget-button widget-button--primary">{{ $t('ui.packages.changesReview') }}</router-link>
        </div>
    </package-base>
</template>

<script>
    import { mapGetters } from 'vuex';
    import routes from '../../router/routes';

    import PackageBase from './Packages/Base';
    import SearchPackage from './Packages/SearchPackage';
    import Loader from '../fragments/Loader';

    export default {
        components: { PackageBase, SearchPackage, Loader },

        props: ['searchField'],

        data: () => ({
            packages: null,
            previousRequest: null,
            algolia: null,

            packageRoute: routes.packages,
        }),

        computed: {
            ...mapGetters('packages', ['totalChanges']),

            query() {
                return this.$route.query.q;
            },
        },

        watch: {
            query(value) {
                this.searchPackages(value);
            },
        },

        methods: {
            searchPackages(value) {
                if (!value) {
                    this.packages = null;
                    return;
                }

                this.$store.dispatch('packages/search', value).then(
                    (packages) => {
                        this.packages = packages;
                    },
                    () => {
                        this.packages = false;
                    },
                );
            },
        },

        mounted() {
            this.$store.dispatch('packages/load').then(() => {
                if (this.searchField) {
                    this.searchField.focus();

                    if (this.query) {
                        this.searchField.setAttribute('value', this.query);
                    }
                }

                if (this.query) {
                    this.searchPackages(this.query);
                }
            });
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
