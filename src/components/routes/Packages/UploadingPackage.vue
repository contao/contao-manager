<template>
    <base-package :title="file.name">
        <template #release>
            <progress-bar :amount="file.progress" />
            <div class="package__version package__version--release">
                <p>
                    <strong>{{ filesize }}</strong>
                </p>
            </div>
        </template>
    </base-package>
</template>

<script>
import BasePackage from './BasePackage';
import ProgressBar from '../../fragments/ProgressBar';
import filesize from '../../../tools/filesize';

export default {
    components: { ProgressBar, BasePackage },

    props: {
        file: {
            type: Object,
            required: true,
        },
    },

    computed: {
        filesize: (vm) => filesize(vm.file.size),
    },
};
</script>
