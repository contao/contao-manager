<template>
    <base-package :title="file.name">
        <template #release>
            <progress-bar :amount="file.progress" />
            <div class="package__version package__version--release">
                <p><strong>{{ filesize }}</strong></p>
            </div>
        </template>
    </base-package>
</template>

<script>
import BasePackage from './BasePackage';
import ProgressBar from '../../fragments/ProgressBar';

export default {
    components: { ProgressBar, BasePackage },

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
                bytes = bytes / 1024;
                size = sizes.shift();
            }

            return `${Math.round(bytes * 100) / 100} ${size}`;
        },
    },
};
</script>
