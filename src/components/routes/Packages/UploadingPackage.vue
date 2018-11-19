<template>
    <package
        :title="file.name"
    >
        <template slot="release">
            <progress-bar :amount="file.progress"/>
            <div class="package__version package__version--release">
                <p><strong>{{ filesize }}</strong></p>
            </div>
        </template>
    </package>
</template>

<script>
    import Package from './Package';
    import ProgressBar from '../../fragments/ProgressBar';
    import ButtonGroup from '../../widgets/ButtonGroup';

    export default {
        components: { ProgressBar, Package, ButtonGroup },

        props: {
            file: {
                type: Object,
                required: true,
            },
            uploader: {
                type: Object,
                required: true,
            },
        },

        computed: {
            filesize() {
                let sizes = ['KB', 'MB', 'GB'];
                let size = 'Bytes';
                let bytes = this.file.size;

                while (bytes > 1024) {
                    bytes = bytes/1024;
                    size = sizes.shift();
                }

                return `${Math.round(bytes * 100) / 100} ${size}`;
            },
        },
    };
</script>
