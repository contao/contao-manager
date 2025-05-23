<template>
    <div v-if="uploads !== null">
        <div v-show="$refs.uploader && $refs.uploader.dropActive" class="package-uploads__overlay">
            <div>
                <img src="../../../assets/images/button-upload.svg" alt="" width="128" height="128" />
                <p>{{ $t('ui.packages.uploadOverlay') }}</p>
            </div>
        </div>

        <file-upload
            name="package"
            ref="uploader"
            post-action="api/packages/uploads"
            :multiple="true"
            :drop="true"
            :drop-directory="false"
            :chunk-enabled="true"
            :chunk="{ action: 'api/packages/uploads' }"
            @update:modelValue="setFiles"
            @input-file="updateFile"
            @input-filter="filterFile"
        ></file-upload>

        <template v-if="$refs.uploader">
            <h2 class="package-list__headline" v-if="hasUploads || files.length">{{ $t('ui.packagelist.uploads') }}</h2>
            <uploading-package v-for="file in files" :file="file" :key="file.id" />
            <uploaded-package v-for="item in unconfirmedUploads" :upload="item" :uploader="$refs.uploader" :key="item.id" />
        </template>
    </div>
</template>

<script>
import { mapState, mapGetters, mapMutations } from 'vuex';
import FileUpload from 'vue-upload-component';
import views from '../../../router/views';
import UploadingPackage from './UploadingPackage';
import UploadedPackage from './UploadedPackage';

export default {
    components: { FileUpload, UploadingPackage, UploadedPackage },

    computed: {
        ...mapState('packages/uploads', ['uploads', 'files']),
        ...mapGetters('packages/uploads', ['hasUploads', 'unconfirmedUploads']),
    },

    methods: {
        ...mapMutations('packages/uploads', ['setUploading', 'setFiles']),

        openFileSelector() {
            if (!this.$refs.uploader) {
                return;
            }

            this.$refs.uploader.$el.querySelector('input').click();
        },

        async filterFile(newFile, oldFile, prevent) {
            // New file has been added
            if (newFile && !oldFile) {
                if (!/\.zip$/i.test(newFile.name)) {
                    return prevent();
                }
            }
        },

        async updateFile(newFile, oldFile) {
            this.setUploading(!this.$refs.uploader.uploaded);

            // File is being deleted
            if (oldFile && !newFile) {
                if (this.$refs.uploader.uploaded && !this.$refs.uploader.active) {
                    this.setFiles([]);
                }
                return;
            }

            if (newFile.error && newFile.xhr) {
                if (newFile.xhr.status === 401) {
                    this.$store.commit('setView', views.LOGIN);
                } else if (newFile.xhr.getResponseHeader('Content-Type') === 'application/problem+json') {
                    this.$store.commit('setError', JSON.parse(newFile.response));
                }
            }

            if (Boolean(newFile) !== Boolean(oldFile) || oldFile.error !== newFile.error) {
                if (!this.$refs.uploader.active) {
                    this.$refs.uploader.active = true;
                }
            }

            if (this.$refs.uploader.uploaded && newFile && oldFile && !newFile.active && oldFile.active) {
                await this.$store.dispatch('packages/uploads/load');
                this.$refs.uploader.remove(newFile);
            }
        },
    },
};
</script>

<style rel="stylesheet/scss" lang="scss">
.package-uploads {
    &__overlay {
        top: 0;
        bottom: 0;
        right: 0;
        left: 0;
        position: fixed;
        z-index: 9999;
        opacity: 0.6;
        text-align: center;
        background: #000;

        div {
            margin: -0.5em 0 0;
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            transform: translateY(-50%);
            font-size: 40px;
            color: #fff;
            padding: 0;
        }
    }
}
</style>
