ARG BASE_IMAGE=mcr.microsoft.com/playwright:v1.61.0-noble
FROM ${BASE_IMAGE}

WORKDIR /tests

COPY package.json package-lock.json ./
RUN npm ci

COPY playwright.config.ts ./
COPY fixtures ./fixtures
COPY lib ./lib
COPY setup ./setup
COPY specs ./specs

CMD ["npm", "test"]
