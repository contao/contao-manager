<template>
    <div class="packages">
        <div v-if="packages === false" class="offline">
            <p>{{ 'ui.packagesearch.offline' | translate }}</p>
            <p>{{ 'ui.packagesearch.offlineExplain' | translate }}</p>
        </div>
        <loader v-else-if="packages === null">
            <p v-if="!query">{{ 'ui.packagesearch.start' | translate }}</p>
            <p v-else v-html="$t('ui.packagesearch.searching', { query })"></p>
        </loader>
        <div v-else-if="!Object.keys(packages).length" class="empty">
            <p v-html="$t('ui.packagesearch.empty', { query })"></p>
        </div>

        <package v-for="item in packages" :name="item.name" :package="item" :key="item.name" :original="originals.get(item.name)" :changed="changes.get(item.name)" @change="changePackage"></package>

        <a href="https://www.algolia.com/" target="_blank" class="algolia"><img src="../../assets/images/search-by-algolia.svg" width="200"></a>
    </div>
</template>

<script>
    /* eslint-disable no-param-reassign */

    import Immutable from 'immutable';

    import routes from '../../router/routes';

    import Package from './Package';
    import Loader from '../fragments/Loader';

    export default {
        components: { Package, Loader },

        props: ['searchField'],

        data: () => ({
            packages: null,
            originals: null,
            changes: null,
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

                        vm.originals = Immutable.fromJS(vm.packages);
                        vm.changes = vm.originals;
                    }
                });
            },

            changePackage(name, changed) {
                this.changes = this.changes.set(name, changed);
                this.$emit('changed', !this.originals.equals(this.changes));
            },

            applyChanges() {
                const require = [];

                this.changes.forEach((data, name) => {
                    if (data.get('constraint') !== this.originals.get(name).get('constraint')) {
                        require.push(`${name} ${data.get('constraint')}`);
                    }
                });

                const task = {
                    type: 'require-package',
                    package: require,
                };

                this.$store.dispatch('tasks/execute', task).then(
                    () => {
                        this.$router.push(routes.packages);
                    },
                );
            },

            resetChanges() {
                this.changes = this.originals;
                this.$emit('changed', false);
            },
        },

        mounted() {
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

<style rel="stylesheet/scss" lang="scss" scoped>
    @import "../../assets/styles/defaults";

    .packages {
        position: relative;
    }

    .packages .empty,
    .packages .offline {
        margin: 100px 0;
        padding-top: 110px;
        text-align: center;
        font-weight: $font-weight-medium;
        font-size: 20px;
        line-height: 1.5em;

        p + p {
            font-size: 16px;
        }
    }

    .packages .empty {
        background: url('../../assets/images/sad.svg') top center no-repeat;
        background-size: 100px 100px;
    }

    .packages .offline {
        background: url('../../assets/images/offline.svg') top center no-repeat;
        background-size: 100px 100px;
    }
</style>
