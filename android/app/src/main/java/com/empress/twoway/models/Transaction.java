package com.empress.twoway.models;

public class Transaction {
    private String type;
    private double amount;
    private String status;
    private String description;
    private String createdAt;

    public Transaction(String type, double amount, String status, String description, String createdAt) {
        this.type = type;
        this.amount = amount;
        this.status = status;
        this.description = description;
        this.createdAt = createdAt;
    }

    public String getType() { return type; }
    public double getAmount() { return amount; }
    public String getStatus() { return status; }
    public String getDescription() { return description; }
    public String getCreatedAt() { return createdAt; }
}
