<template>
    <div class="widget widget-text" :class="{ 'widget--error': error, 'widget--required': required }">
        <label v-if="label" :for="'ctrl_'+name">{{ label }}</label>
        <input
            ref="input"
            :type="type ? type : 'text'"
            :id="label ? 'ctrl_'+name : ''"
            :name="name"
            :placeholder="placeholder"
            :required="required"
            :disabled="disabled"
            :value="value"
            @input="input($event.target.value)"
            @keyup="$emit('keyup')"
            @focus="$emit('focus')"
            @blur="$emit('blur')"
            autocapitalize="none"
        >
        <p class="widget__error" v-if="error">{{ error }}</p>
    </div>
</template>

<script>
    export default {
        props: {
            type: {
                type: String,
                validator: value => ['text', 'tel', 'email', 'url', 'password', 'search'].includes(value),
            },
            name: {
                type: String,
                required: true,
            },
            label: String,
            value: String,
            pattern: String,
            placeholder: String,
            disabled: Boolean,
            required: Boolean,
            error: String,
        },

        methods: {
            input(value) {
                this.$emit('input', value);
            },
            enter() {
                this.$emit('enter');
            },
            focus() {
                this.$refs.input.focus();
            },
        },

        mounted() {
            this.$emit('input', this.$refs.input.value);
        },
    };
</script>
