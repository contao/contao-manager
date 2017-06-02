<template>
    <div class="widget select">
        <label v-if="label" :for="'ctrl_'+name">{{ label }}</label>
        <select
            :id="label ? 'ctrl_'+name : ''"
            :name="name"
            :disabled="disabled"
            @change="input($event.target.value)"
        >
            <option v-for="(v, k) in options" :value="k" :selected="k === value">{{ v }}</option>
        </select>
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
    };
</script>
