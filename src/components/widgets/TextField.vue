<template>
    <div :class="'widget widget-text' + (error ? ' widget--error' : '')">
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
        <p class="widget__error" v-if="error">{{ error }}</p>
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
