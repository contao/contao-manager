<template>
    <div class="error">
        <div>
            <span>ERROR</span> {{ error.message }}

{{ error.exception }}
        </div>
    </div>
</template>

<script>
    import { mapState } from 'vuex';

    export default {
        computed: mapState(['error']),
    };
</script>
