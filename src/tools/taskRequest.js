export default async function taskRequest(request) {
    for (;;) {
        try {
            return await request();
        } catch (error) {
            const response = error.response;
            const retryAfter = Number(response?.headers?.['retry-after']);

            if (response?.status !== 503 || !Number.isFinite(retryAfter) || retryAfter <= 0) {
                throw error;
            }

            // Retry the original operation so cancellation and continuation are not lost.
            await new Promise((resolve) => setTimeout(resolve, retryAfter * 1000));
        }
    }
}
