<template>
    <fieldset class="user-scope">
        <legend v-if="label">{{ label }}</legend>
        <!-- eslint-disable vue/no-v-for-template-key -->
        <template v-for="scope in all" :key="scope">
            <check-box
                class="user-scope__item" :class="{ 'user-scope__item--required': isRequired(scope) }"
                :name="scope"
                :label="$t(`ui.scope.${scope}`)"
                :disabled="!isRequested(scope) || isRequired(scope)"
                :model-value="model[scope]"
                @update:model-value="value => setEnabled(scope, value)"
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
        scopes: vm => vm.all.filter(r => !vm.allowed || vm.allowed.includes(r)),

        isRequested: vm => scope => vm.scopes.includes(scope),
        isRequired: vm => scope => vm.all.indexOf(scope) <= vm.all.indexOf(vm.all.find(s => vm.scopes.includes(s))),
    },

    methods: {
        init () {
            this.all.forEach((scope) => {
                this.model[scope] = false;
            });

            this.setEnabled(this.modelValue || this.scopes[this.scopes.length - 1], true);
        },

        setEnabled (scope, value) {
            this.all.forEach((r) => {
                if (this.isRequired(r)) {
                    this.model[r] = true;
                } else if (value) {
                    this.model[r] = this.all.indexOf(r) <= this.all.indexOf(scope);
                } else {
                    this.model[r] = this.isRequested(r) && this.all.indexOf(r) < this.all.indexOf(scope);
                }
            });

            this.$emit('update:modelValue', Array.from(this.all).reverse().find(k => this.model[k]));
        },
    },

    watch: {
        scopes: {
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
.user-scope {
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
