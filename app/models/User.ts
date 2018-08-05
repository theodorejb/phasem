export interface User {
    id: string;
    fullName: string;
    email: string;
    dateCreated: string;
}

export interface LoginCredentials {
    email: string;
    password: string;
}

export interface NewUser extends LoginCredentials {
    fullName: string;
}

export interface UserProfile {
    fullName: string;
}

export interface UserEmail {
    email: string;
}

export interface PasswordChange {
    currentPassword: string;
    newPassword: string;
}
