<template>
    <div :class="'widget text-field' + (errorMessage ? ' invalid' : '')">
        <label v-if="label" :for="'ctrl_'+name">{{ label }}</label>
        <input
            ref="input"
            :type="type ? type : 'text'"
            :id="label ? 'ctrl_'+name : ''"
            :name="name"
            :placeholder="placeholder"
            :disabled="disabled"
            :value="value"
            @input="input($event.target.value)"
            @keypress.enter.prevent="enter"
            autocapitalize="none"
        >
        <p class="error" v-if="errorMessage">{{ errorMessage }}</p>
    </div>
</template>

<script>
    export default {
        props: {
            type: {
                type: String,
                validator: value => value === 'text' || value === 'password',
            },
            name: {
                type: String,
                required: true,
            },
            label: {
                type: String,
            },
            value: {
                type: String,
            },
            placeholder: {
                type: String,
            },
            disabled: {
                type: Boolean,
            },
            error: {
                type: String,
            },
            mandatory: {
                type: Boolean,
            },
            mandatoryMessage: {
                type: String,
            },
        },

        data: () => ({
            invalid: null,
        }),

        computed: {
            errorMessage() {
                if (this.error) {
                    return this.error;
                }

                if (this.invalid) {
                    return this.invalid;
                }

                return null;
            },
        },

        methods: {
            input(value) {
                this.$emit('input', value);

                this.invalid = null;

                if (value.length === 0 && this.mandatory) {
                    this.invalid = this.mandatoryMessage || this.$t('ui.widget.mandatory');
                }
            },
            enter() {
                this.$emit('enter');
            },
            focus() {
                this.$refs.input.focus();
            },
        },

        mounted() {
            if (!this.value && this.mandatory) {
                this.invalid = this.mandatoryMessage || this.$t('ui.widget.mandatory');
            }
        },
    };
</script>
