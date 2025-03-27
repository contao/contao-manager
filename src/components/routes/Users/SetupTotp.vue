<template>
    <popup-overlay class="setup-totp" :headline="$t('ui.totp.headline')" @submit="submit" @clear="close">
        <template v-if="provisioning_uri">
            <p>{{ $t('ui.totp.setupQr') }}</p>

            <div class="setup-totp__qr">
                <qrcode-svg :value="provisioning_uri" level="H" />
            </div>

            <p>{{ $t('ui.totp.setupCode') }}</p>

            <code class="setup-totp__uri">{{ provisioning_uri }}</code>

            <text-field
                ref="totp" name="totp"
                :label="$t('ui.totp.codeLabel')" :description="$t('ui.totp.codeDescription')"
                required pattern="\d+" minlength="6" maxlength="6"
                autocomplete="one-time-code"
                :error="error" @keyup="error = ''"
                v-model="totp"
            />
        </template>
        <loading-spinner v-else />

        <template #actions v-if="provisioning_uri">
            <button type="button" class="widget-button" :disabled="loading" @click="close">{{ $t('ui.totp.cancel') }}</button>
            <loading-button submit color="primary" :loading="loading">{{ $t('ui.totp.enable') }}</loading-button>
        </template>
    </popup-overlay>
</template>

<script>
import axios from 'axios';
import { mapState } from 'vuex';
import PopupOverlay from 'contao-package-list/src/components/fragments/PopupOverlay';
import LoadingSpinner from 'contao-package-list/src/components/fragments/LoadingSpinner';
import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';
import TextField from '../../widgets/TextField.vue';
import { QrcodeSvg } from 'qrcode.vue';

export default {
    components: { PopupOverlay, LoadingSpinner, LoadingButton, TextField, QrcodeSvg },

    data: () => ({
        loading: false,
        provisioning_uri: null,
        totp: null,
        error: '',
    }),

    computed: {
        ...mapState('auth', ['username']),
    },

    methods: {
        submit() {
            this.loading = true;

            this.$request.put(
                `api/users/${this.username}/totp`,
                {
                    provisioning_uri: this.provisioning_uri,
                    totp: this.totp,
                },
                null,
                {
                    201: async () => {
                        await this.$store.dispatch('auth/status');
                        this.$notify.success(this.$t('ui.totp.enabled'));
                        this.close();
                    },
                    422: () => {
                        this.loading = false;
                        this.error = this.$t('ui.totp.invalid');
                        this.$refs.totp.focus();
                    },
                },
            );
        },

        close() {
            this.$store.commit('modals/close', 'setup-totp');
        },
    },

    async mounted() {
        const response = await axios.get(`api/users/${this.username}/totp`);

        this.provisioning_uri = response.data.provisioning_uri;

        setTimeout(() => {
            this.$refs.totp.focus();
        }, 0);
    },
};
</script>

<style rel="stylesheet/scss" lang="scss">
.setup-totp {
    &__qr {
        margin: 2em;
        text-align: center;

        svg {
            width: 200px;
            height: 200px;
        }
    }

    &__uri {
        display: block;
        margin: 1em 0;
        word-break: break-all;
    }

    .sk-circle {
        margin: 20px auto;
    }
}
</style>
