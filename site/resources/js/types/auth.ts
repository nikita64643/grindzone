export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string | null;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    /** Отображаемый никнейм (по умолчанию name) */
    nickname?: string;
    /** Статус пользователя (например "Онлайн", "Играет") */
    status?: string;
    /** Баланс (число) */
    balance?: number;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
