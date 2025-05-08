export async function registerUser({ name, email, password }) {
  const response = await fetch('/api/auth/register', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ name, email, password }),
  });
  const result = await response.json();
  if (!response.ok || result.error) {
    throw result;
  }
  return result;
}
