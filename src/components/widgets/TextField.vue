<template>
    <div class="widget text-field">
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
        >
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
    };
</script>
