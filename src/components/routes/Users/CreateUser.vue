<template>
    <popup-overlay name="create-user" class="create-user" headline="Create User" @submit="submit">
        <p class="create-user__text">Fill in the fields to add a new user account.</p>

        <text-field
            type="text" name="username"
            label="Username"
            required
            :disabled="loading"
            v-model="username"
        />

        <text-field
            type="password" name="password"
            label="Password" :placeholder="$t('ui.account.passwordPlaceholder')"
            required minlength="8"
            :disabled="loading"
            v-model="password"
        />

        <text-field
            type="email"
            name="email" label="E-Mail Address" placeholder="optional"
            :disabled="loading"
            v-model="email"
        />

        <user-roles label="Permissions" v-model="role"/>

        <template #actions>
            <loading-button submit color="primary" :loading="loading">Create account</loading-button>
            <button class="widget-button" :disabled="loading" @click="cancel">Cancel</button>
        </template>
    </popup-overlay>
</template>

<script>
    import PopupOverlay from 'contao-package-list/src/components/fragments/PopupOverlay';
    import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';
    import TextField from '../../widgets/TextField';
    import UserRoles from './UserRoles';

    export default {
        components: { TextField, PopupOverlay, LoadingButton, UserRoles },

        data: () => ({
            loading: false,

            username: '',
            password: '',
            email: '',
            role: 'admin',
        }),

        methods: {
            async submit() {
                this.loading = true;
                const response = await this.$store.dispatch('users/create', {
                    username: this.username,
                    password: this.password,
                    roles: [`ROLE_${this.role.toUpperCase()}`],
                    email: this.email,
                });

                if (response.status === 201) {
                    await this.$store.dispatch('users/get', false);
                    this.$store.commit('modals/close', 'create-user');
                }
            },

            cancel() {
                this.$store.commit('modals/close', 'create-user');
            },
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
@use "~contao-package-list/src/assets/styles/defaults";

.create-user {
    &__text {
        margin: 2em 0;
    }

    .widget-text {
        margin: 1em 0;
    }
}
</style>
