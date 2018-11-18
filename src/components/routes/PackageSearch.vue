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
                v-model="searchInput"
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

    export default {
        components: { PackageBase, SearchPackage, Loader },

        data: () => ({
            results: null,
            previousRequest: null,
            searchInput: '',

            packageRoute: routes.packages,
        }),

        computed: {
            ...mapState('packages', ['installed']),
            ...mapGetters('packages', ['totalChanges']),

            query() {
                return this.$route.query.q;
            },
        },

        watch: {
            searchInput() {
                this.$router.push(
                    Object.assign(
                        { query: { q: this.searchInput } },
                        routes.packagesSearch,
                    ),
                );
            },

            query(value) {
                this.searchPackages(value);
            },
        },

        methods: {
            async searchPackages(value) {
                this.searchInput = value;

                if (!value) {
                    this.results = null;
                    return;
                }

                try {
                    this.results = await this.$store.dispatch('packages/search/find', value);
                } catch (err) {
                    this.results = false;
                }
            },

            stopSearch() {
                this.searchInput = '';
                this.$router.push(routes.packages);
            },
        },

        async mounted() {
            if (null === this.installed) {
                await this.$store.dispatch('packages/load')
            }

            if (this.query) {
                this.searchPackages(this.query);
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

        &__algolia {
            display: block;
            margin: 50px 0 0;
            text-align: center;
        }
    }
</style>
