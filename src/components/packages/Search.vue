<template>
    <div class="packages">

        <div class="scope">
            <h2>{{ 'ui.packagesearch.typeHeadline' | translate }}</h2>
            <fieldset :class="{ active: scope === 'contao-bundle' }">
                <input type="radio" name="scope" id="scope-bundle" value="contao-bundle" v-model="scope">
                <label for="scope-bundle">{{ 'ui.packagesearch.typeBundle' | translate }}</label>
            </fieldset>
            <fieldset :class="{ active: scope === 'contao-module' }">
                <input type="radio" name="scope" id="scope-module" value="contao-module" v-model="scope">
                <label for="scope-module">{{ 'ui.packagesearch.typeModule' | translate }}</label>
            </fieldset>
        </div>

        <div v-if="packages === false" class="offline">
            <p>{{ 'ui.packagesearch.offline' | translate }}</p>
        </div>
        <loader v-else-if="packages === null">
            <p v-if="!query">{{ 'ui.packagesearch.start' | translate }}</p>
            <p v-else v-html="$t('ui.packagesearch.searching', { query })"></p>
        </loader>
        <div v-else-if="!Object.keys(packages).length" class="empty">
            <p v-html="$t('ui.packagesearch.empty', { query })"></p>
            <a v-if="scope === 'contao-module'" @click="scope = 'contao-bundle'">{{ 'ui.packagesearch.searchBundles' | translate }}</a>
            <a v-else-if="scope === 'contao-bundle'" @click="scope = 'contao-module'">{{ 'ui.packagesearch.searchModules' | translate }}</a>
        </div>

        <transition-group name="package">
            <package v-for="item in packages" :name="item.name" :package="item" :key="item.name" :original="originals.get(item.name)" :changed="changes.get(item.name)" @change="changePackage"></package>
        </transition-group>
    </div>
</template>

<script>
    /* eslint-disable no-param-reassign */

    import Immutable from 'immutable';
    import debounce from 'lodash.debounce';
    import throttle from 'lodash.throttle';

    import routes from '../../router/routes';

    import Package from './Package';
    import Loader from '../fragments/Loader';

    export default {
        components: { Package, Loader },

        data: () => ({
            packages: null,
            originals: null,
            changes: null,
            previousRequest: null,
            scope: 'contao-bundle',
        }),

        computed: {
            query() {
                return this.$route.query.q;
            },
        },

        watch: {
            scope() {
                this.packages = null;
                this.searchPackages(this, this.query);
            },
            query(value) {
                this.searchPackages(this, value);
            },
        },

        methods: {
            searchPackages: debounce(
                (vm, value) => {
                    if (!value) {
                        vm.originals = null;
                        vm.changes = null;
                        vm.packages = null;
                        return;
                    }

                    vm.$http.get(
                        `https://packagist.org/search.json?per_page=10&type=${vm.scope}&q=${value}`,
                        {
                            before(request) {
                                if (this.previousRequest) {
                                    this.previousRequest.abort();
                                }

                                this.previousRequest = request;
                            },
                        },
                    ).then(
                        response => response.json().then(
                            (data) => {
                                vm.updatePackages(vm, data);
                            },
                        ),
                        (response) => {
                            if (response.status === 0) {
                                vm.packages = false;
                            } else {
                                throw response;
                            }
                        },
                    );
                },
                100,
            ),

            updatePackages: throttle(
                (vm, data) => {
                    vm.packages = {};

                    data.results.forEach((pkg) => {
                        vm.packages[pkg.name] = pkg;
                    });

                    vm.originals = Immutable.fromJS(vm.packages);
                    vm.changes = vm.originals;
                },
                1000,
            ),

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
        font-family: $font-regular;
        font-size: 20px;

        a {
            display: block;
            margin-top: 20px;
            font-family: $font-default;
            font-size: 0.8em;
            cursor: pointer;
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

    .package {
        display: block;
        width: 100%;
        transition: all 1s;
    }
    .package-enter, .package-leave-to {
        opacity: 0;
        transform: translateY(30px);
    }
    .package-leave-active {
        position: absolute;
    }
</style>
