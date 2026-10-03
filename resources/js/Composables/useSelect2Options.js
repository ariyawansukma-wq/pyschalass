import { ref } from 'vue';
import { useHttp } from '@inertiajs/vue3';

export function useSelect2Options(endpoint, mapper = null) {
  const http = useHttp({});
  const options = ref([]);
  const loading = ref(false);
  let timeoutId = null;
  let activeResolve = null;

  const fetchOptions = (q = '', extraParams = {}) => {
    return new Promise((resolve) => {
      if (activeResolve) {
        activeResolve([]);
      }
      activeResolve = resolve;

      clearTimeout(timeoutId);
      loading.value = true;

      timeoutId = setTimeout(async () => {
        try {
          let url = `/api/select2/${endpoint}?q=${encodeURIComponent(q)}`;
          Object.entries(extraParams).forEach(([k, v]) => {
            if (v !== undefined && v !== null) {
              url += `&${k}=${encodeURIComponent(v)}`;
            }
          });
          const res = await http.get(url);
          const mapped = mapper
            ? res.results.map(mapper)
            : res.results.map(r => ({ label: r.text, value: Number(r.id) }));
          options.value = mapped;
          resolve(mapped);
        } catch (err) {
          console.error(`Error fetching select2 options for ${endpoint}:`, err);
          resolve([]);
        } finally {
          loading.value = false;
          if (activeResolve === resolve) {
            activeResolve = null;
          }
        }
      }, 300); // 300ms debounce
    });
  };

  return {
    options,
    loading,
    fetchOptions,
  };
}
