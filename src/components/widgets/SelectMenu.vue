<template>
    <div class="widget widget-select" :class="{ 'widget--error': error, 'widget--required': required }">
        <label v-if="label" :for="'ctrl_'+name">{{ label }}</label>
        <select
            ref="input"
            :id="label ? 'ctrl_'+name : ''"
            :name="name"
            :disabled="disabled"
            :required="required"
            @change="input($event.target.value)"
        >
            <option v-for="(v, k) in options" :value="k" :selected="k === value" :key="k">{{ v }}</option>
        </select>
        <p class="widget__error" v-if="error">{{ error }}</p>
    </div>
</template>

<script>
    export default {
        props: {
            name: {
                type: String,
                required: true,
            },
            options: {
                type: Object,
                required: true,
            },
            label: String,
            value: String,
            disabled: Boolean,
            required: Boolean,
            error: String,
        },

        methods: {
            input(value) {
                this.$emit('input', value);
            },
        },

        mounted() {
            this.$emit('input', this.$refs.input.value);
        },

        updated() {
            this.$emit('input', this.$refs.input.value);
        },
    };
</script>
