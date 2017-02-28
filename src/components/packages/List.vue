<template>
    <div>
        <loader v-if="packages === null">
            <p>Loading …</p>
        </loader>

        <package v-for="(item, name) in packages" :name="name" :package="item" :key="name" :original="originals.get(name)" :changed="changes.get(name)" @change="changePackage"></package>
    </div>
</template>

<script>
    import Immutable from 'immutable';

    import api from '../../api';

    import Package from './Package';
    import Loader from '../fragments/Loader';

    export default {
        components: { Package, Loader },
        data: () => ({
            packages: null,
            originals: null,
            changes: null,
        }),
        methods: {
            listPackages() {
                this.packages = null;
                this.originals = this.changes = null;

                api.getPackages().then(
                    (packages) => {
                        this.packages = packages;
                        this.originals = this.changes = Immutable.fromJS(packages);
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

                if (removed.length && updated.length) {
                    alert('Cannot remove and change constraint');
                    return;
                }

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
