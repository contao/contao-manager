import { mapState } from 'vuex';

export default {
    computed: {
        ...mapState('tasks', { taskStatus: 'status', currentTask: 'current', deletingTask: 'deleting', awaitTask: 'await' }),

        hasTask: vm => vm.currentTask && vm.currentTask.status,
        isActive: vm => vm.hasTask && vm.taskStatus === 'active',
        isComplete: vm => vm.hasTask && vm.taskStatus === 'complete',
        isAborting: vm => vm.hasTask && vm.taskStatus === 'aborting',
        isFailed: vm => vm.taskStatus === 'failed',
        isError: vm => vm.hasTask && (vm.taskStatus === 'error' || vm.taskStatus === 'stopped' || vm.taskStatus === 'failed'),

        allowAutoClose: vm => vm.hasTask && vm.currentTask.autoclose,
        allowCancel: vm => vm.hasTask && vm.currentTask.cancellable,

        requiresAudit: vm => vm.isComplete && vm.currentTask.audit,
    },
}
