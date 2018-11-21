<template>
    <div :class="'widget widget-select' + (error ? ' widget--error' : '')">
        <label v-if="label" :for="'ctrl_'+name">{{ label }}</label>
        <select
            ref="input"
            :id="label ? 'ctrl_'+name : ''"
            :name="name"
            :disabled="disabled"
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
            label: {
                type: String,
            },
            value: {
                type: String,
            },
            disabled: {
                type: Boolean,
            },
            error: {
                type: String,
            },
            options: {
                type: Object,
                required: true,
            },
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
