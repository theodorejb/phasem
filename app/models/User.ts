export interface User {
    id: string,
    fullName: string,
    email: string,
    dateCreated: string,
}

export interface LoginCredentials {
    email: string,
    password: string,
}

export interface NewUser extends LoginCredentials {
    fullName: string,
}
