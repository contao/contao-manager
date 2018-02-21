<template>
    <div class="package-list">
        <loader v-if="packages === null" class="package-list__status">
            <p>{{ 'ui.packagelist.loading' | translate }}</p>
        </loader>

        <package v-for="item in packages" :package="item" :key="item.name"/>
        <package v-for="item in $store.state.packages.add" :package="item" :key="item.name"/>
    </div>
</template>

<script>
    import Package from './Package';
    import Loader from '../fragments/Loader';

    export default {
        components: { Package, Loader },

        data: () => ({
            packages: null,
        }),

        methods: {
            listPackages() {
                this.packages = null;

                this.$store.dispatch('packages/list').then((packages) => {
                    this.packages = packages;
                });
            },

            applyChanges() {
                // TODO implement server side update task
                alert('FIXME');
                // const removed = [];
                // const updated = [];
                //
                // this.changes.forEach((data, name) => {
                //     if (data.get('constraint') === null) {
                //         removed.push(name);
                //     } else if (
                // data.get('constraint') !== this.originals.get(name).get('constraint')) {
                //         updated.push(`${name} ${data.get('constraint')}`);
                //     }
                // });
                //
                // let task;
                //
                // if (removed.length) {
                //     task = {
                //         name: 'composer/remove',
                //         config: {
                //             packages: removed,
                //         },
                //     };
                // } else {
                //     task = {
                //         type: 'composer/require',
                //         config: {
                //             packages: updated,
                //         },
                //     };
                // }
                //
                // this.$store.dispatch('tasks/execute', task).then(
                //     () => {
                //         this.$emit('changed', false);
                //         this.listPackages();
                //     },
                // );

                // const require = [];
                //
                // this.changes.forEach((data, name) => {
                //     if (data.get('constraint') !== this.originals.get(name).get('constraint')) {
                //         require.push(`${name} ${data.get('constraint')}`);
                //     }
                // });
                //
                // const task = {
                //     name: 'composer/require',
                //     config: {
                //         packages: require,
                //     },
                // };
                //
                // this.$store.dispatch('tasks/execute', task).then(
                //     () => {
                //         this.$router.push(routes.packages);
                //     },
                // );
            },
        },

        mounted() {
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
