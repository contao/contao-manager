<template>
    <package-base show-search>
        <template slot="search">
            <input
                class="package-tools__search"
                ref="search"
                slot="search"
                type="text"
                :placeholder="$t('ui.packages.searchPlaceholder')"
                autocomplete="off"
                :value="this.query"
                @input="searchInput"
                @keypress.esc.prevent="stopSearch"
            >
            <button class="package-tools__cancel" @click="stopSearch">
                <svg height="24" viewBox="0 0 24 24" width="24" fill="#737373" xmlns="http://www.w3.org/2000/svg"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/><path d="M0 0h24v24H0z" fill="none"/></svg>
            </button>
        </template>

        <div class="package-search">
            <div v-if="results === false" class="package-search__status package-search__status--offline">
                <p class="package-search__title">{{ 'ui.packagesearch.offline' | translate }}</p>
                <p class="package-search__explain">{{ 'ui.packagesearch.offlineExplain' | translate }}</p>
            </div>
            <div v-else-if="results === null && !query" class="package-search__status package-search__status--start">
                <p class="package-search__title">{{ 'ui.packagesearch.start' | translate }}</p>
            </div>
            <loader v-else-if="results === null" class="package-search__status package-search__status--loader">
                <p class="package-search__title" v-html="$t('ui.packagesearch.searching', { query })"></p>
            </loader>
            <div v-else-if="!Object.keys(results).length" class="package-search__status package-search__status--empty">
                <p class="package-search__title" v-html="$t('ui.packagesearch.empty', { query })"></p>
            </div>

            <search-package v-for="item in results" :package="item" :key="item.name"/>

            <div class="package-search__more">
                <loading-button inline icon="search" :loading="searching" v-if="hasMore" @click="loadMore">{{ $t('ui.packagesearch.more') }}</loading-button>
            </div>

            <a href="https://www.algolia.com/" target="_blank" class="package-search__algolia"><img src="../../assets/images/search-by-algolia.svg" width="200"></a>
        </div>

        <div class="package-actions__inner" slot="actions" v-if="totalChanges">
            <p class="package-actions__text">{{ $t('ui.packages.changesMessage', { total: totalChanges }, totalChanges) }}</p>
            <router-link :to="packageRoute" class="package-actions__button widget-button widget-button--primary">{{ $t('ui.packages.changesReview') }}</router-link>
        </div>
    </package-base>
</template>

<script>
    import { mapState, mapGetters } from 'vuex';
    import routes from '../../router/routes';

    import PackageBase from './Packages/Base';
    import SearchPackage from './Packages/SearchPackage';
    import Loader from '../fragments/Loader';
    import LoadingButton from '../widgets/LoadingButton';

    export default {
        components: { PackageBase, SearchPackage, Loader, LoadingButton },

        data: () => ({
            results: null,
            previousRequest: null,
            hasMore: false,
            searching: false,

            packageRoute: routes.packages,
        }),

        computed: {
            ...mapState('packages', ['installed']),
            ...mapGetters('packages', ['totalChanges']),

            query: vm => vm.$route.query.q,
            pages: vm => vm.$route.query.pages || 1,
        },

        methods: {
            async searchPackages() {
                if (!this.query) {
                    this.results = null;
                    return;
                }

                this.searching = true;

                try {
                    const response = await this.$store.dispatch('packages/search/find', {
                        query: this.query,
                        hitsPerPage: 10 * this.pages,
                    });

                    this.hasMore = response.nbPages > 1;

                    if (response.nbHits === 0) {
                        this.results = {};
                        return;
                    }

                    const packages = {};

                    response.hits.forEach((pkg) => {
                        packages[pkg.name] = pkg;
                    });

                    this.results = packages;

                } catch (err) {
                    this.results = false;
                }

                this.searching = false;
            },

            loadMore() {
                this.updateRoute(this.query, this.pages + 1);
            },

            updateRoute(q, pages = 1) {
                const query = { q };

                if (pages > 1) {
                    query.pages = pages;
                }

                this.$router.push(
                    Object.assign(
                        { query },
                        routes.packagesSearch,
                    ),
                );
            },

            searchInput(e) {
                this.updateRoute(e.target.value);
            },

            stopSearch() {
                this.$router.push(routes.packages);
            },
        },

        watch: {
            query() {
                this.searchPackages();
            },

            pages() {
                this.searchPackages();
            },
        },

        async mounted() {
            if (null === this.installed) {
                await this.$store.dispatch('packages/load')
            }

            if (this.query) {
                this.searchPackages();
            }

            this.$nextTick(() => {
                this.$refs.search.focus();
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

        &__more {
            margin: 30px 0 60px;
            text-align: center;

            button {
                padding-left: 50px;
                padding-right: 50px;
            }
        }

        &__algolia {
            display: block;
            width: 200px;
            margin: 50px auto 0;
        }
    }
</style>
