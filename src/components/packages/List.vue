<template>
    <div class="package-list">
        <loader v-if="packages === null" class="package-list__status">
            <p>{{ 'ui.packagelist.loading' | translate }}</p>
        </loader>

        <package v-for="(item, name) in packages" :name="name" :package="item" :key="name" :original="originals.get(name)" :changed="changes.get(name)" :disableUpdate="hasRemoves" :disableRemove="hasUpdates" @change="changePackage"/>
    </div>
</template>

<script>
    import Vue from 'vue';
    import Immutable from 'immutable';

    import Package from './Package';
    import Loader from '../fragments/Loader';

    export default {
        components: { Package, Loader },
        data: () => ({
            packages: null,
            originals: null,
            changes: null,
        }),

        computed: {
            hasUpdates() {
                let result = false;

                this.changes.forEach((data, name) => {
                    if (data.get('constraint') !== null
                        && data.get('constraint') !== this.originals.get(name).get('constraint')
                    ) {
                        result = true;
                    }
                });

                return result;
            },

            hasRemoves() {
                let result = false;

                this.changes.forEach((data) => {
                    if (data.get('constraint') === null) {
                        result = true;
                    }
                });

                return result;
            },
        },

        methods: {
            listPackages() {
                this.packages = null;
                this.originals = null;
                this.changes = null;

                Vue.http.get('api/packages').then(
                    (response) => {
                        this.packages = Object.assign(
                            {},
                            { 'contao/manager-bundle': response.body['contao/manager-bundle'] },
                            response.body,
                        );

                        this.originals = Immutable.fromJS(this.packages);
                        this.changes = this.originals;
                    },
                );
            },

            changePackage(name, changed) {
                this.changes = this.changes.set(name, changed);
                this.$emit('changed', !this.originals.equals(this.changes));
            },

            applyChanges() {
                const removed = [];
                const updated = [];

                this.changes.forEach((data, name) => {
                    if (data.get('constraint') === null) {
                        removed.push(name);
                    } else if (data.get('constraint') !== this.originals.get(name).get('constraint')) {
                        updated.push(`${name} ${data.get('constraint')}`);
                    }
                });

                let task;

                if (removed.length) {
                    task = {
                        type: 'remove-package',
                        package: removed,
                    };
                } else {
                    task = {
                        type: 'require-package',
                        package: updated,
                    };
                }

                this.$store.dispatch('tasks/execute', task).then(
                    () => {
                        this.$emit('changed', false);
                        this.listPackages();
                    },
                );
            },

            resetChanges() {
                this.changes = this.originals;
                this.$emit('changed', false);
            },
        },
        created() {
            this.listPackages();
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "../../assets/styles/defaults";

    .package-list {
        position: relative;

        &__status {
            margin: 100px 0;
            text-align: center;
            font-size: 20px;
            line-height: 1.5em;

            .sk-circle {
                width: 100px;
                height: 100px;
                margin: 0 auto 40px;
            }
        }
    }
</style>
