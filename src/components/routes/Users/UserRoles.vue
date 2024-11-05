<template>
    <fieldset class="user-roles">
        <legend v-if="label">{{ label }}</legend>
        <!-- eslint-disable vue/no-v-for-template-key -->
        <template v-for="role in all" :key="role">
            <check-box
                class="user-roles__item" :class="{ 'user-roles__item--required': isRequired(role) }"
                :name="role"
                :label="$t(`ui.role.${role}`)"
                :disabled="!isRequested(role) || isRequired(role)"
                :model-value="model[role]"
                @update:model-value="value => setEnabled(role, value)"
            />
        </template>
    </fieldset>
</template>

<script>
import CheckBox from '../../widgets/CheckBox.vue';

export default {
    compatConfig: { COMPONENT_V_MODEL: false },

    components: { CheckBox },

    props: {
        allowed: Array,
        label: String,
        modelValue: String,
    },

    data: () => ({
        all: ['read', 'update', 'install', 'admin'],

        model: {
            admin: false,
            install: false,
            update: false,
            read: false,
        },
    }),

    computed: {
        roles: vm => vm.all.filter(r => !vm.allowed || vm.allowed.includes(r)),

        isRequested: vm => role => vm.roles.includes(role),
        isRequired: vm => role => vm.all.indexOf(role) <= vm.all.indexOf(vm.all.find(s => vm.roles.includes(s))),
    },

    methods: {
        init () {
            this.all.forEach((role) => {
                this.model[role] = false;
            });

            this.setEnabled(this.modelValue || this.roles[this.roles.length - 1], true);
        },

        setEnabled (role, value) {
            this.all.forEach((r) => {
                if (this.isRequired(r)) {
                    this.model[r] = true;
                } else if (value) {
                    this.model[r] = this.all.indexOf(r) <= this.all.indexOf(role);
                } else {
                    this.model[r] = this.isRequested(r) && this.all.indexOf(r) < this.all.indexOf(role);
                }
            });

            this.$emit('update:modelValue', Array.from(this.all).reverse().find(k => this.model[k]));
        },
    },

    watch: {
        roles: {
            handler () {
                this.init();
            },
            deep: true,
        },
        modelValue: {
            handler (value) {
                this.setEnabled(value, true);
            },
            deep: true,
        }
    },

    mounted() {
        this.init();
    }
}
</script>

<style rel="stylesheet/scss" lang="scss">
.user-roles {
    &__item {
        padding: 5px 0 0;

        &--required label {
            opacity: 1 !important;

            &:before {
                opacity: 0.5;
            }
        }
    }
}
</style>
