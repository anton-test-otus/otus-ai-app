import { z } from 'zod';

// Настройка русских сообщений об ошибках для Zod
const customErrorMap: z.ZodErrorMap = (issue, ctx) => {
  if (issue.code === z.ZodIssueCode.invalid_type) {
    if (issue.expected === 'string') {
      return { message: 'Поле обязательно для заполнения' };
    }
  }
  if (issue.code === z.ZodIssueCode.too_small) {
    if (issue.type === 'string') {
      if (issue.minimum === 1) {
        return { message: 'Поле обязательно для заполнения' };
      }
      return { message: `Минимум ${issue.minimum} символов` };
    }
  }
  return { message: ctx.defaultError };
};

z.setErrorMap(customErrorMap);

export { z };
