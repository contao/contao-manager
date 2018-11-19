<template>
    <package
        :title="data.name"
        :hint="hint"
        v-if="!data.success || data.error"
    >
        <template slot="release">
            <progress-bar :amount="progress"/>
            <div class="package__version package__version--release">
                <p><strong>{{ filesize }}</strong></p>
            </div>
        </template>

        <template slot="actions">
            <button :class="removeClass" :disabled="removing" @click="removeUpload">
                <span v-if="!removing">{{ $t('ui.package.removeButton') }}</span>
                <loader v-else/>
            </button>
        </template>
    </package>

    <package
        :title="pkg.title"
        :name="pkg.name"
        :description="pkg.description"
        release-disabled
        v-else
    >
        <more private :name="pkg.name" :homepage="pkg.homepage" :support="Object.assign({}, pkg.support)" slot="more"/>

        <template slot="additional">
            <strong class="package__version package__version--additional">{{ 'ui.package.version' | translate({ version: pkg.version }) }}</strong>
            <span v-for="(item,k) in additional" :key="k">{{ item }}</span>
        </template>

        <template slot="release">
            <fieldset>
                <input type="text" :placeholder="$t('ui.package.latestConstraint')" disabled>
                <button class="widget-button" disabled>{{ 'ui.package.editConstraint' | translate }}</button>
            </fieldset>
            <div class="package__version package__version--release">
                <strong>{{ 'ui.package.version' | translate({ version: pkg.version }) }}</strong>
            </div>
        </template>

        <template slot="actions">
            <button-group :label="$t('ui.package.installButton')" type="primary" icon="add" :disabled="removing" @click="addPackage">
                <button :class="removeClass" :disabled="removing" @click="removeUpload">
                    <span v-if="!removing">{{ $t('ui.package.removeButton') }}</span>
                    <loader v-else/>
                </button>
            </button-group>
        </template>
    </package>

</template>

<script>
    import Loader from '../../fragments/Loader';
    import Package from './Package';
    import More from './More';
    import ProgressBar from '../../fragments/ProgressBar';
    import ButtonGroup from '../../widgets/ButtonGroup';

    export default {
        components: { Loader, ProgressBar, Package, More, ButtonGroup },

        props: {
            id: {
                type: String,
                required: true,
            },
            data: {
                type: Object,
                required: true,
            },
            uploader: {
                type: Object,
                required: true,
            },
        },

        data: () => ({
            removing: false,
        }),

        computed: {
            progress: vm => 100 / vm.data.size * vm.data.filesize,

            removeClass: vm => ({
                'widget-button widget-button--alert': true,
                'widget-button--trash': !vm.removing
            }),

            pkg() {
                return Object.assign(
                    {
                        name: this.data.name,
                    },
                    this.data.package || {}
                );
            },

            hint() {
                if (this.data.error) {
                    return this.data.error;
                }

                if (this.data.size !== this.data.filesize) {
                    return 'This file was not uploaded completely.';
                }

                return '';
            },

            filesize() {
                let sizes = ['KB', 'MB', 'GB'];
                let size = 'Bytes';
                let bytes = this.data.size;

                while (bytes > 1024) {
                    bytes = bytes/1024;
                    size = sizes.shift();
                }

                return `${Math.round(bytes * 100) / 100} ${size}`;
            },

            additional() {
                const additionals = [];

                if (this.pkg.license) {
                    if (this.pkg.license instanceof Array) {
                        additionals.push(this.pkg.license.join('/'));
                    } else {
                        additionals.push(this.pkg.license);
                    }
                }

                if (this.pkg.downloads) {
                    additionals.push(this.$t('ui.package.additionalDownloads', { count: this.pkg.downloads }, this.pkg.downloads));
                }

                if (this.pkg.favers) {
                    additionals.push(this.$t('ui.package.additionalStars', { count: this.pkg.favers }, this.pkg.favers));
                }

                return additionals;
            },
        },

        methods: {
            addPackage() {
                // TODO add package
            },

            removeUpload() {
                this.removing = true;
                this.$store.dispatch('packages/uploads/remove', this.id);
            },
        },
    };
</script>
