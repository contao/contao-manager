<template>
    <div class="packages">

        <div class="scope">
            <h2>Search for</h2>
            <fieldset :class="{ active: scope === 'contao-bundle' }">
                <input type="radio" name="scope" id="scope-bundle" value="contao-bundle" v-model="scope">
                <label for="scope-bundle">Contao 4 bundles</label>
            </fieldset>
            <fieldset :class="{ active: scope === 'contao-module' }">
                <input type="radio" name="scope" id="scope-module" value="contao-module" v-model="scope">
                <label for="scope-module">Contao 3 extensions</label>
            </fieldset>
        </div>

        <div v-if="packages === false" class="offline">
            <p>Could not fetch results. Are you online?</p>
        </div>
        <loader v-else-if="packages === null">
            <p v-if="!query">Enter a keyword to start searching …</p>
            <p v-else>Searching for Contao packages matching <i>{{ query }}</i> …</p>
        </loader>
        <div v-else-if="!Object.keys(packages).length" class="empty">
            <p>No results for <i>{{ query }}</i></p>
            <a v-if="scope === 'contao-module'" @click="scope = 'contao-bundle'">Search for Contao 4 bundles</a>
            <a v-else-if="scope === 'contao-bundle'" @click="scope = 'contao-module'">Search for Contao 3 extensions</a>
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
                /*
                if (this.packages !== null && !Object.keys(this.packages).length) {
                    this.packages = null;
                }
                */

                this.searchPackages(this, value);
            },
        },

        methods: {
            searchPackages: debounce(
                (vm, value) => {
                    if (!value) {
                        vm.originals = vm.changes = vm.packages = null;
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

                    vm.originals = vm.changes = Immutable.fromJS(vm.packages);
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
                        this.$router.push({ name: 'packages' });
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
