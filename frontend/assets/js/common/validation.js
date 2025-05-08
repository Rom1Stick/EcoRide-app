export function validateEmail(email) {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(email);
}

export function validatePassword(password) {
  const lengthValid = password.length >= 8 && password.length <= 15;
  const upper = /[A-Z]/.test(password);
  const lower = /[a-z]/.test(password);
  const digit = /\d/.test(password);
  const special = /[!@#$%^&*(),.?":{}|<>]/.test(password);
  return lengthValid && upper && lower && digit && special;
}

export function validateConfirmPassword(password, confirmPassword) {
  return password === confirmPassword;
}

export function validateTerms(accepted) {
  return accepted === true;
}

// Validation du nom (3 à 15 lettres, sans caractères spéciaux)
export function validateName(name) {
  // Unicode property
  const re = /^[\p{L} ]{3,15}$/u;
  return re.test(name.trim());
}
